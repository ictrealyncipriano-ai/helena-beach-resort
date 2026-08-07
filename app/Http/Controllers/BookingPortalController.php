<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\GuardsBookingAccess;
use App\Http\Controllers\Admin\DashboardController;
use App\Mail\BookingCancelled;
use App\Mail\RefundReceived;
use App\Models\Inquiry;
use App\Models\SiteSetting;
use App\Services\PayMongoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Guest-facing booking portal: lookup bookings by email + reference code,
 * view booking details, and self-cancel (with 24h cutoff).
 *
 * All portal routes are gated on a session-held booking token (see
 * GuardsBookingAccess) so guessing an auto-increment {inquiry} id alone is
 * never enough to view or mutate a booking.
 */
class BookingPortalController extends Controller
{
    use GuardsBookingAccess;

    /** Show email/reference lookup form */
    public function lookupForm()
    {
        return view('pages.booking-lookup');
    }

    /** Find a booking by email + reference code */
    public function lookup(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'reference_code' => 'required|string',
        ]);

        $inquiry = Inquiry::where('email', $validated['email'])
            ->where('reference_code', $validated['reference_code'])
            ->first();

        if (! $inquiry) {
            return back()->withErrors([
                'reference_code' => 'No booking found with that email and reference code.',
            ])->withInput();
        }

        // Grant session access before redirecting so the portal pages work.
        $this->grantBookingAccess($inquiry);

        return redirect()->route('booking.portal.show', $inquiry);
    }

    /** Show booking detail page with cancellation option */
    public function show(Inquiry $inquiry)
    {
        $this->authorizeBookingAccess($inquiry);

        $inquiry->load('cottage');

        $canCancel = $this->canCancel($inquiry);

        return view('pages.booking-detail', [
            'inquiry' => $inquiry,
            'canCancel' => $canCancel,
            'cancelBlockReason' => $canCancel ? null : $this->cannotCancelReason($inquiry),
        ]);
    }

    /**
     * Lightweight JSON status used by the booking-detail payment poller.
     * Session-gated exactly like every other portal route so a guessed
     * {inquiry} id alone can never reveal booking state.
     */
    public function status(Inquiry $inquiry): JsonResponse
    {
        $this->authorizeBookingAccess($inquiry);

        return response()->json([
            'paid' => $inquiry->isPaid(),
            'status' => $inquiry->status,
        ]);
    }

    /** Cancel a booking (guest-facing), sends notification to guest and owner */
    public function cancel(Request $request, Inquiry $inquiry, PayMongoService $payMongo)
    {
        $this->authorizeBookingAccess($inquiry);

        if (! $this->canCancel($inquiry)) {
            return back()->with('error', 'This booking cannot be cancelled. Cancellations must be made at least 24 hours before check-in.');
        }

        $refunded = false;
        $refundFailed = false;
        $refundAlreadyProcessed = false;

        // Auto-refund the full amount if this booking was already paid.
        if ($inquiry->isPaid()) {
            // Atomically claim the refund so two concurrent cancels can never
            // both reach the PayMongo refund API (double-refund TOCTOU guard).
            $claimed = Inquiry::where('id', $inquiry->id)
                ->whereNotNull('paid_at')
                ->whereNull('refunded_at')
                ->update(['refunded_at' => now()]);

            if ($claimed === 1) {
                try {
                    $payMongo->refund($inquiry);
                    $refunded = true;
                } catch (\RuntimeException $e) {
                    Log::warning('Auto-refund failed on guest cancellation', [
                        'inquiry_id' => $inquiry->id,
                        'error' => $e->getMessage(),
                    ]);
                    // Roll the claim back so the guest (or an admin) can retry.
                    // A model-level update() is not enough here: the in-memory
                    // refunded_at is still null (the claim was a bulk update),
                    // so the attribute is never dirty and would be skipped.
                    Inquiry::where('id', $inquiry->id)->update(['refunded_at' => null]);
                    $refundFailed = true;
                }
            } else {
                // Another request (or the admin) already processed the refund.
                $refundAlreadyProcessed = true;
            }
        }

        // Reload so refunded_at/refund_amount reflect whatever state the
        // database holds after the claim above (a concurrent writer may have
        // set them), then update only the booking status.
        $wasConfirmed = $inquiry->status === 'confirmed';
        $inquiry->refresh();

        $inquiry->update([
            'status' => 'cancelled',
            'refunded_at' => $refunded ? now() : $inquiry->refunded_at,
            'refund_amount' => $refunded
                ? ($inquiry->paid_amount ?? $inquiry->total_amount)
                : $inquiry->refund_amount,
        ]);
        $inquiry->releaseBlocks();

        // Only decrement a recorded stay when this was a confirmed booking
        // (markConfirmed() increments it); never let a cancel push a pending
        // booking's count below zero.
        if ($wasConfirmed && $inquiry->guest && $inquiry->guest->total_stays > 0) {
            $inquiry->guest->decrement('total_stays');
        }

        // Guest cancellations change the same dashboard aggregates as admin
        // ones (pending/confirmed counts, revenue), so drop the cached stats.
        Cache::forget(DashboardController::cacheKey());

        try {
            Mail::to($inquiry->email)->send(new BookingCancelled($inquiry));

            if ($refunded) {
                Mail::to($inquiry->email)->send(new RefundReceived($inquiry->fresh()));
            }

            $ownerEmail = SiteSetting::getValue('contact_email');
            if ($ownerEmail) {
                Mail::to($ownerEmail)->send(new BookingCancelled($inquiry));
            }
        } catch (\Exception $e) {
            Log::warning('Failed to send cancellation notification', [
                'inquiry_id' => $inquiry->id,
                'error' => $e->getMessage(),
            ]);
        }

        return redirect()->route('booking.portal.show', $inquiry)
            ->with(
                ($refundFailed || $refundAlreadyProcessed) ? 'warning' : 'success',
                $refundFailed
                    ? 'Your booking has been cancelled, but the refund could not be processed automatically. Please contact the resort to complete your refund.'
                    : ($refunded
                        ? 'Your booking has been cancelled and your payment has been refunded.'
                        : ($refundAlreadyProcessed
                            ? 'Your booking has been cancelled. The refund was already processed.'
                            : 'Your booking has been cancelled.'))
            );
    }

    /**
     * Check if cancellation is allowed:
     * - Status must be pending or confirmed
     * - Must have a check-in date that is at least 24 hours in the future
     */
    private function canCancel(Inquiry $inquiry): bool
    {
        if (! in_array($inquiry->status, ['pending', 'confirmed'])) {
            return false;
        }

        if (! $inquiry->check_in || $inquiry->check_in->isPast()) {
            return false;
        }

        if (now()->diffInHours($inquiry->check_in) < 24) {
            return false;
        }

        return true;
    }

    /**
     * Explain why a pending/confirmed booking cannot be cancelled, so the
     * portal can show a clear reason instead of silently omitting the action.
     * Returns null when the booking can be cancelled.
     */
    private function cannotCancelReason(Inquiry $inquiry): ?string
    {
        if (! in_array($inquiry->status, ['pending', 'confirmed'])) {
            return null;
        }

        if (! $inquiry->check_in || $inquiry->check_in->isPast()) {
            return 'This booking can no longer be cancelled.';
        }

        if (now()->diffInHours($inquiry->check_in) < 24) {
            return 'Cancellation is no longer available. This booking can be cancelled until 24 hours before check-in (cutoff: '
                .$inquiry->check_in->format('M d, Y').').';
        }

        return null;
    }
}
