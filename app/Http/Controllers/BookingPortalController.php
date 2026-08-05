<?php

namespace App\Http\Controllers;

use App\Mail\BookingCancelled;
use App\Mail\RefundReceived;
use App\Models\Inquiry;
use App\Models\SiteSetting;
use App\Services\PayMongoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Guest-facing booking portal: lookup bookings by email + reference code,
 * view booking details, and self-cancel (with 24h cutoff).
 */
class BookingPortalController extends Controller
{
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

        return redirect()->route('booking.portal.show', $inquiry);
    }

    /** Show booking detail page with cancellation option */
    public function show(Inquiry $inquiry)
    {
        $canCancel = $this->canCancel($inquiry);

        return view('pages.booking-detail', [
            'inquiry' => $inquiry,
            'canCancel' => $canCancel,
            'cancelBlockReason' => $canCancel ? null : $this->cannotCancelReason($inquiry),
        ]);
    }

    /** Cancel a booking (guest-facing), sends notification to guest and owner */
    public function cancel(Request $request, Inquiry $inquiry, PayMongoService $payMongo)
    {
        if (! $this->canCancel($inquiry)) {
            return back()->with('error', 'This booking cannot be cancelled. Cancellations must be made at least 24 hours before check-in.');
        }

        $refunded = false;
        $refundFailed = false;

        // Auto-refund the full amount if this booking was already paid.
        if ($inquiry->isPaid() && ! $inquiry->isRefunded()) {
            try {
                $payMongo->refund($inquiry);
                $refunded = true;
            } catch (\RuntimeException $e) {
                Log::warning('Auto-refund failed on guest cancellation', [
                    'inquiry_id' => $inquiry->id,
                    'error' => $e->getMessage(),
                ]);
                $refundFailed = true;
            }
        }

        $inquiry->update([
            'status' => 'cancelled',
            'refunded_at' => $refunded ? now() : $inquiry->refunded_at,
            'refund_amount' => $refunded
                ? ($inquiry->paid_amount ?? $inquiry->total_amount)
                : $inquiry->refund_amount,
        ]);
        $inquiry->releaseBlocks();

        if ($inquiry->guest) {
            $inquiry->guest->decrement('total_stays');
        }

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
                $refundFailed ? 'warning' : 'success',
                $refundFailed
                    ? 'Your booking has been cancelled, but the refund could not be processed automatically. Please contact the resort to complete your refund.'
                    : ($refunded
                        ? 'Your booking has been cancelled and your payment has been refunded.'
                        : 'Your booking has been cancelled.')
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
