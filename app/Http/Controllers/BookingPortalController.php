<?php

namespace App\Http\Controllers;

use App\Concerns\CancelsBookings;
use App\Exceptions\BookingConflictException;
use App\Http\Controllers\Concerns\GuardsBookingAccess;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Requests\Portal\BookingLookupRequest;
use App\Http\Requests\Portal\BookingModifyRequest;
use App\Http\Requests\Portal\BookingReviewRequest;
use App\Http\Requests\Portal\PaymentProofRequest;
use App\Mail\BookingModified;
use App\Models\Cottage;
use App\Models\Inquiry;
use App\Models\SiteSetting;
use App\Models\Testimonial;
use App\Queries\BlockedDates;
use App\Services\ActivityLogger;
use App\Services\BookingCancellationService;
use App\Services\BookingEligibility;
use App\Services\BookingModificationService;
use App\Services\InquiryService;
use App\Services\PayMongoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

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
    use CancelsBookings;

    private const CUTOFF_HOURS = 24;

    public function __construct(
        private ActivityLogger $logger,
        private BookingEligibility $eligibility,
        private BookingModificationService $modificationService,
        private BookingCancellationService $cancellationService,
    ) {
    }

    /** Show email/reference lookup form */
    public function lookupForm(): View
    {
        return view('pages.booking-lookup');
    }

    /** Find a booking by email + reference code */
    public function lookup(BookingLookupRequest $request): RedirectResponse
    {
        $validated = $request->validated();

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
    public function show(Inquiry $inquiry): View
    {
        $this->authorizeBookingAccess($inquiry);

        $inquiry->load('cottage');

        $canCancel = $this->eligibility->canCancel($inquiry);
        $canModify = $this->eligibility->canModify($inquiry);

        return view('pages.booking-detail', [
            'inquiry' => $inquiry,
            'canCancel' => $canCancel,
            'cancelBlockReason' => $canCancel ? null : $this->eligibility->cannotCancelReason($inquiry),
            'canModify' => $canModify,
            'modifyBlockReason' => $canModify ? null : $this->eligibility->cannotModifyReason($inquiry),
            'canReview' => $this->eligibility->canReview($inquiry),
            'canSubmitPaymentProof' => $this->eligibility->canSubmitPaymentProof($inquiry),
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

    /**
     * Submit a guest review from the booking portal. Reviews are created
     * inactive so the resort can moderate them before they go public.
     */
    public function review(BookingReviewRequest $request, Inquiry $inquiry): RedirectResponse
    {
        $this->authorizeBookingAccess($inquiry);

        if (! $this->eligibility->canReview($inquiry)) {
            return back()->with('error', 'This booking is not eligible for a review yet.');
        }

        $data = $request->validated();

        Testimonial::create([
            'guest_name' => $inquiry->name,
            'guest_email' => $inquiry->email,
            'content' => $data['content'],
            'rating' => (int) $data['rating'],
            'cottage_id' => $inquiry->cottage_id,
            'inquiry_id' => $inquiry->id,
            'source' => Inquiry::SOURCE_GUEST,
            'is_active' => false,
        ]);

        $this->logger->record('guest.reviewed', $inquiry, "Guest submitted a review for {$inquiry->reference_code}.", [
            'rating' => (int) $data['rating'],
        ]);

        return redirect()->route('booking.portal.show', $inquiry)
            ->with('success', 'Thank you! Your review has been submitted and is pending approval.');
    }

    /**
     * Upload a proof of a manual payment (bank transfer, GCash, etc.) from
     * the booking portal. The image is stored privately and flagged for admin
     * review; it is not shown publicly and never linked from an unauthenticated
     * URL.
     */
    public function uploadPaymentProof(PaymentProofRequest $request, Inquiry $inquiry): RedirectResponse
    {
        $this->authorizeBookingAccess($inquiry);

        if (! $this->eligibility->canSubmitPaymentProof($inquiry)) {
            return back()->with('error', 'This booking is not eligible to submit a payment proof right now.');
        }

        $data = $request->validated();

        $inquiry->update([
            'payment_proof_path' => $request->file('payment_proof')->store('payment-proofs', 'cloudflare'),
            'payment_proof_status' => Inquiry::PROOF_PENDING,
            'payment_proof_submitted_at' => now(),
            'payment_proof_reviewed_at' => null,
            'payment_proof_review_note' => null,
        ]);

        $this->logger->record('guest.payment_proof', $inquiry, "Guest uploaded a payment proof for {$inquiry->reference_code}.");

        return redirect()->route('booking.portal.show', $inquiry)
            ->with('success', 'Thank you! Your payment proof has been uploaded and is pending review by the resort.');
    }

    /**
     * Show the modify form, pre-filled with the current schedule. Dates held
     * by this very booking are excluded from the disabled list so the guest
     * can keep their current dates (or shift a pending request).
     */
    public function modifyForm(Inquiry $inquiry): View|RedirectResponse
    {
        $this->authorizeBookingAccess($inquiry);
        $inquiry->load('cottage');

        if (! $this->eligibility->canModify($inquiry)) {
            $reason = $this->eligibility->cannotModifyReason($inquiry);

            return redirect()->route('booking.portal.show', $inquiry)
                ->with('error', $reason ?? 'This booking cannot be modified right now.');
        }

        $cottages = Cottage::available()->get();

        // Any future block not held by this booking is disabled in the
        // pickers. FK-primary: NULL-safe exclude of own inquiry_id in SQL,
        // legacy reason-string fallback covers pre-backfill rows.
        $blockedByCottage = BlockedDates::byCottageExcludingInquiry(
            $cottages->pluck('id'),
            $inquiry->id,
            $inquiry->reference_code,
        );

        $rates = Cottage::ratesMap($cottages);

        return view('pages.booking-modify', compact('inquiry', 'cottages', 'blockedByCottage', 'rates'));
    }

    /**
     * Apply a guest-initiated schedule change. The old blocks are released
     * and the new ones held inside one transaction, so a conflict on the new
     * dates leaves the original booking (and its hold) untouched.
     */
    public function modify(BookingModifyRequest $request, Inquiry $inquiry, InquiryService $inquiryService): RedirectResponse
    {
        $this->authorizeBookingAccess($inquiry);

        if (! $this->eligibility->canModify($inquiry)) {
            $reason = $this->eligibility->cannotModifyReason($inquiry);

            return back()->with('error', $reason ?? 'This booking cannot be modified right now.');
        }

        $validated = $request->validated();

        $inquiry->load('cottage');
        $original = [
            'cottage_id' => $inquiry->cottage_id,
            'check_in' => $inquiry->check_in?->format('Y-m-d'),
            'check_out' => $inquiry->check_out?->format('Y-m-d'),
        ];
        $previous = $this->snapshotForEmail($inquiry);
        $wasConfirmed = $inquiry->status === Inquiry::STATUS_CONFIRMED;

        try {
            $inquiry = $this->modificationService->apply($inquiry, $validated, $original, $wasConfirmed, $inquiryService);
        } catch (BookingConflictException $e) {
            return back()->with('error', 'Those dates are no longer available. Your original booking was not changed.')
                ->withInput();
        }

        // A schedule change shifts the dashboard's pending/confirmed counts and
        // revenue, so drop the cached aggregates like every other write path.
        DashboardController::forgetCache();

        $this->logger->record('guest.modified', $inquiry, "Guest modified booking {$inquiry->reference_code}.", [
            'previous' => $previous,
        ]);

        $this->sendModifiedNotifications($inquiry, $previous);

        return redirect()->route('booking.portal.show', $inquiry)
            ->with('success', 'Your booking has been updated. A confirmation email is on its way.');
    }

    /**
     * Human-readable description of the schedule before a change, used both
     * for the activity log and the "before" column of the modification email.
     *
     * @return array{cottage: string, booking_type: string, check_in: ?string, check_out: ?string, pax: ?int}
     */
    private function snapshotForEmail(Inquiry $inquiry): array
    {
        return [
            'cottage' => $inquiry->cottage?->name ?? 'Not specified',
            'booking_type' => $inquiry->booking_type === Inquiry::TYPE_DAY_TOUR
                ? 'Day Tour'
                : ($inquiry->booking_type === Inquiry::TYPE_OVERNIGHT ? 'Overnight' : 'Inquiry'),
            'check_in' => $inquiry->check_in?->format('M d, Y'),
            'check_out' => $inquiry->check_out?->format('M d, Y'),
            'pax' => $inquiry->pax,
        ];
    }

    /**
     * Notify the guest (and the resort owner) of the schedule change. Runs
     * outside the transaction like the other portal emails, so a failed mail
     * can never roll back a successful modification.
     *
     * @param  array<string, mixed>|null  $previous
     */
    private function sendModifiedNotifications(Inquiry $inquiry, ?array $previous): void
    {
        try {
            Mail::to($inquiry->email)->send(new BookingModified($inquiry, $previous));

            $ownerEmail = SiteSetting::getValue('contact_email');
            if ($ownerEmail) {
                Mail::to($ownerEmail)->send(new BookingModified($inquiry, $previous));
            }
        } catch (\Exception $e) {
            Log::warning('Failed to send booking modification notification', [
                'inquiry_id' => $inquiry->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /** Cancel a booking (guest-facing), sends notification to guest and owner */
    public function cancel(Request $request, Inquiry $inquiry, PayMongoService $payMongo): RedirectResponse
    {
        $this->authorizeBookingAccess($inquiry);

        if (! $this->eligibility->canCancel($inquiry)) {
            return back()->with('error', 'This booking cannot be cancelled. Cancellations must be made at least ' . self::CUTOFF_HOURS . ' hours before check-in.');
        }

        $refundState = $this->cancellationService->processRefund($inquiry, $payMongo);

        $this->cancellationService->finalizeCancellation($inquiry, $refundState['wasConfirmed']);
        $this->cancellationService->sendGuestCancellationEmails($inquiry, $refundState);
        $this->logger->record('guest.cancelled', $inquiry, "Guest cancelled booking {$inquiry->reference_code}.", [
            'refunded' => $refundState['refunded'],
            'refund_failed' => $refundState['refundFailed'],
            'manual_refund_required' => $refundState['manualRefundRequired'],
        ]);

        return redirect()->route('booking.portal.show', $inquiry)
            ->with($this->cancellationService->cancellationFlashType($refundState), $this->cancellationService->cancellationFlashMessage($inquiry, $refundState));
    }
}
