<?php

namespace App\Http\Controllers\Admin;

use App\Concerns\CancelsBookings;
use App\Concerns\ConfirmsBookings;
use App\Exceptions\BookingConflictException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\InquiryRequest;
use App\Mail\RefundReceived;
use App\Models\Cottage;
use App\Models\Guest;
use App\Models\Inquiry;
use App\Services\ActivityLogger;
use App\Services\InquiryService;
use App\Services\PayMongoService;
use App\Services\RefundService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class InquiryController extends Controller
{
    use CancelsBookings;
    use ConfirmsBookings;

    public function __construct(
        private ActivityLogger $logger,
        private RefundService $refundService
    ) {
    }

    public function index(Request $request): View
    {
        $query = Inquiry::with(['cottage', 'guest']);

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('reference_code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('booking_type')) {
            $query->where('booking_type', $request->booking_type);
        }

        if ($request->filled('source')) {
            $query->where('source', $request->source);
        }

        if ($request->filled('cottage_id')) {
            $query->where('cottage_id', $request->cottage_id);
        }

        $inquiries = $query->latest()->paginate(self::ADMIN_PER_PAGE)->withQueryString();
        $cottages = Cottage::pluck('name', 'id');
        $guests = Guest::pluck('name', 'id');
        $cottageRates = Cottage::ratesMap(
            Cottage::select('id', 'name', 'rate_daytour', 'rate_overnight', 'peak_start', 'peak_end', 'peak_rate_daytour', 'peak_rate_overnight')->get()
        );

        return view('admin.inquiries.index', compact('inquiries', 'cottages', 'guests', 'cottageRates'));
    }

    /**
     * Store a walk-in booking taken over the counter or by phone. Tags it
     * with source = walk-in, auto-creates/links a guest profile when no
     * existing guest is selected, and auto-calculates the total from the
     * cottage rate when the amount is left blank.
     */
    public function store(InquiryRequest $request, InquiryService $inquiryService): RedirectResponse
    {
        $data = $request->validated();

        $totalAmount = $data['total_amount'] ?? null;
        if ($totalAmount === null || $totalAmount === '') {
            $totalAmount = $inquiryService->calculateTotal($data);
        }

        $inquiry = Inquiry::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'check_in' => $data['check_in'] ?? null,
            'check_out' => $data['check_out'] ?? null,
            'pax' => $data['pax'] ?? null,
            'cottage_id' => $data['cottage_id'] ?? null,
            'guest_id' => $data['guest_id'] ?? null,
            'message' => $data['message'] ?? null,
            'booking_type' => $data['booking_type'] ?? null,
            'total_amount' => $totalAmount,
            'deposit_amount' => $data['deposit_amount'] ?? null,
            'status' => $data['status'],
            'source' => Inquiry::SOURCE_WALKIN,
            'reference_code' => Inquiry::generateReferenceCode(),
        ]);

        $this->linkGuest($inquiry);

        try {
            $this->activateBlocks($inquiry);
        } catch (BookingConflictException $e) {
            // The inquiry was never usable (its blocks rolled back), so it
            // should not linger as a trashed row — fully remove it.
            $inquiry->forceDelete();

            return back()->with('error', $e->getMessage())->withInput();
        }

        DashboardController::forgetCache();

        $this->logger->record('inquiry.created', $inquiry, "Walk-in inquiry {$inquiry->reference_code} created.");

        return redirect()->route('admin.inquiries.index')
            ->with('success', "Walk-in inquiry {$inquiry->reference_code} created successfully.");
    }

    /**
     * Auto-link a guest profile for a walk-in booking when no existing guest
     * was selected. Reuses and restores a soft-deleted profile instead of
     * colliding with the unique guests.email index.
     */
    private function linkGuest(Inquiry $inquiry): void
    {
        if (! empty($inquiry->guest_id) || ! $inquiry->email) {
            return;
        }

        // Include soft-deleted rows in the lookup: SoftDeletes hides
        // trashed guests from updateOrCreate, whose INSERT would then
        // collide with the unique guests.email index (HTTP 500). Reuse and
        // restore the trashed profile instead. Emails are matched
        // case-insensitively and normalized, so an email typed with
        // different casing never creates a duplicate profile.
        // Admin-entered name/phone are trusted here, so they are refreshed
        // on the record.
        $guest = Guest::findByEmailOrCreate(
            $inquiry->email,
            ['name' => $inquiry->name, 'phone' => $inquiry->phone]
        );

        if ($guest->trashed()) {
            $guest->restore();
            // A trashed profile carries another person's history; reset
            // the transient stay/notes fields so the 'new' guest does
            // not inherit it.
            $guest->update(['total_stays' => 0, 'last_stay_at' => null, 'notes' => null]);
        }

        $guest->update(['name' => $inquiry->name, 'phone' => $inquiry->phone]);
        $inquiry->guest()->associate($guest)->save();
    }

    /**
     * Hold the inquiry's date blocks, promoting to booked (and recording the
     * guest stay) when the booking is created directly as confirmed.
     */
    private function activateBlocks(Inquiry $inquiry): void
    {
        DB::transaction(function () use ($inquiry) {
            if ($inquiry->status === Inquiry::STATUS_CONFIRMED) {
                $inquiry->bookBlocks();
                $this->markConfirmed($inquiry);
            } elseif ($inquiry->status === Inquiry::STATUS_PENDING) {
                $inquiry->reserveBlocks();
            }
        });
    }

    public function show(Inquiry $inquiry): View
    {
        $inquiry->load(['cottage', 'guest']);

        return view('admin.inquiries.show', compact('inquiry'));
    }

    public function edit(Inquiry $inquiry): View
    {
        $inquiry->load(['cottage', 'guest']);
        $cottages = Cottage::pluck('name', 'id');
        $guests = Guest::pluck('name', 'id');

        return view('admin.inquiries.form', compact('inquiry', 'cottages', 'guests'));
    }

    public function update(InquiryRequest $request, Inquiry $inquiry): RedirectResponse
    {
        $data = $request->validated();

        $original = [
            'cottage_id' => $inquiry->cottage_id,
            'check_in' => $inquiry->check_in?->format('Y-m-d'),
            'check_out' => $inquiry->check_out?->format('Y-m-d'),
        ];

        $wasConfirmed = $inquiry->status === Inquiry::STATUS_CONFIRMED;

        // Release the blocks held for the original schedule, then re-hold
        // for the new schedule so stale blocks never linger. All changes are
        // transactional: if the new dates are taken, nothing is changed and
        // the original hold is preserved.
        try {
            DB::transaction(function () use ($inquiry, $data, $original, $wasConfirmed) {
                $inquiry->update($data);
                $inquiry->refresh();

                $inquiry->releaseBlocks($original);

                if ($inquiry->status === Inquiry::STATUS_CONFIRMED) {
                    $inquiry->bookBlocks();

                    if (! $wasConfirmed) {
                        $this->markConfirmed($inquiry);
                    }
                } elseif ($inquiry->status === Inquiry::STATUS_PENDING) {
                    $inquiry->reserveBlocks();
                }

                // A previously-confirmed booking moved off confirmed
                // (cancelled/expired): reverse the stay that markConfirmed()
                // recorded so the guest's count never drifts upward.
                if ($wasConfirmed && $inquiry->status !== Inquiry::STATUS_CONFIRMED) {
                    $inquiry->reverseStay();
                }
            });
        } catch (BookingConflictException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        DashboardController::forgetCache();

        $this->logger->record('inquiry.updated', $inquiry, "Inquiry {$inquiry->reference_code} updated.", [
            'previous' => [
                'cottage_id' => $original['cottage_id'],
                'check_in' => $original['check_in'],
                'check_out' => $original['check_out'],
            ],
        ]);

        return redirect()->route('admin.inquiries.index')
            ->with('success', 'Inquiry updated successfully.');
    }

    public function destroy(Inquiry $inquiry): RedirectResponse
    {
        $inquiry->releaseBlocks();
        $inquiry->delete();
        DashboardController::forgetCache();
        $this->logger->record('inquiry.deleted', $inquiry, "Inquiry {$inquiry->reference_code} deleted.");

        return redirect()->route('admin.inquiries.index')
            ->with('success', 'Inquiry deleted successfully.');
    }

    /**
     * Record a manually-collected settlement (bank transfer / cash-on-site).
     * The admin states how much money actually arrived: a partial (deposit)
     * payment is recorded as such and the booking stays confirmed-unpaid
     * until the balance is settled.
     */
    public function markPaid(Request $request, Inquiry $inquiry): RedirectResponse
    {
        if ($inquiry->status !== Inquiry::STATUS_CONFIRMED) {
            return back()->with('error', 'Only confirmed bookings can be marked as paid.');
        }

        if ($inquiry->isPaid()) {
            return back()->with('error', 'This booking has already been paid.');
        }

        $balance = formatPrice(max((float) $inquiry->total_amount - $inquiry->collectedAmount(), 0), 2, false);

        if ((float) $balance <= 0) {
            return back()->with('error', 'This booking has no outstanding balance.');
        }

        $validated = $request->validate([
            'amount' => ['nullable', 'numeric', 'min:0.01', 'max:'.$balance],
        ], [
            'amount.max' => 'The amount exceeds the outstanding balance of '.formatPrice($balance).'.',
        ]);

        // No explicit amount (e.g. the one-click list action) settles the
        // whole outstanding balance.
        $amount = isset($validated['amount'])
            ? formatPrice($validated['amount'], 2, false)
            : $balance;
        $fullyPaid = $inquiry->recordManualPayment($amount);

        DashboardController::forgetCache();

        $this->logger->record('inquiry.marked_paid', $inquiry, "Manual payment of ".formatPrice($amount)." recorded for {$inquiry->reference_code}.");

        $message = $fullyPaid
            ? "Booking {$inquiry->reference_code} marked as fully paid."
            : 'Payment of '.formatPrice($amount).' recorded. Remaining balance: '.formatPrice($inquiry->fresh()->balanceDue()).'.';

        return redirect()->route('admin.inquiries.show', $inquiry)
            ->with('success', $message);
    }

    /**
     * Approve a guest-submitted payment proof and record the money it
     * represents. The admin states how much actually arrived (defaults to
     * the amount currently due) so a deposit proof no longer marks the
     * whole booking as fully paid.
     */
    public function approvePaymentProof(Request $request, Inquiry $inquiry): RedirectResponse
    {
        if (! $inquiry->hasPendingPaymentProof()) {
            return back()->with('error', 'This booking has no payment proof awaiting review.');
        }

        $balance = formatPrice(max((float) $inquiry->total_amount - $inquiry->collectedAmount(), 0), 2, false);

        $validated = $request->validate([
            'note' => 'nullable|string|max:500',
            'amount' => ['nullable', 'numeric', 'min:0.01', 'max:'.$balance],
        ], [
            'amount.max' => 'The amount exceeds the outstanding balance of '.formatPrice($balance).'.',
        ]);

        $inquiry->update([
            'payment_proof_status' => Inquiry::PROOF_APPROVED,
            'payment_proof_reviewed_at' => now(),
            'payment_proof_review_note' => $validated['note'] ?? null,
        ]);

        if (! $inquiry->isPaid()) {
            // Default to what the guest was asked to pay at this point
            // (deposit when unpaid, otherwise the remaining balance).
            $amount = isset($validated['amount'])
                ? formatPrice($validated['amount'], 2, false)
                : $inquiry->amountDueNow();

            $fullyPaid = (float) $amount > 0 && $inquiry->recordManualPayment($amount);
        } else {
            $fullyPaid = true;
        }

        DashboardController::forgetCache();

        $this->logger->record('payment_proof.approved', $inquiry, "Payment proof for {$inquiry->reference_code} approved.");

        $message = $fullyPaid
            ? "Payment proof for {$inquiry->reference_code} approved and booking marked as paid."
            : 'Payment proof for '.$inquiry->reference_code.' approved'.(isset($amount) ? " (".formatPrice($amount)." recorded)" : '').'. Remaining balance: '.formatPrice($inquiry->fresh()->balanceDue()).'.';

        return redirect()->route('admin.inquiries.show', $inquiry)
            ->with($fullyPaid ? 'success' : 'warning', $message);
    }

    /**
     * Reject a guest-submitted payment proof. The booking stays unpaid and
     * the guest can upload a new proof.
     */
    public function rejectPaymentProof(Request $request, Inquiry $inquiry): RedirectResponse
    {
        if (! $inquiry->hasPendingPaymentProof()) {
            return back()->with('error', 'This booking has no payment proof awaiting review.');
        }

        $note = $request->validate([
            'note' => 'nullable|string|max:500',
        ]);

        $inquiry->update([
            'payment_proof_status' => Inquiry::PROOF_REJECTED,
            'payment_proof_reviewed_at' => now(),
            'payment_proof_review_note' => $note['note'] ?? null,
        ]);

        $this->logger->record('payment_proof.rejected', $inquiry, "Payment proof for {$inquiry->reference_code} rejected.");

        return redirect()->route('admin.inquiries.show', $inquiry)
            ->with('success', "Payment proof for {$inquiry->reference_code} was rejected. The guest can upload a new one.");
    }

    /**
     * Refund a booking with collected money via PayMongo and cancel it.
     * Refunds exactly what was collected (deposit included), not the full
     * total; manually-collected money has no PayMongo reference and must be
     * returned offline.
     */
    public function refund(Inquiry $inquiry, PayMongoService $payMongo): RedirectResponse
    {
        if (! $inquiry->hasPayments()) {
            return redirect()->route('admin.inquiries.show', $inquiry)
                ->with('error', 'This booking has no payment to refund.');
        }

        try {
            $claimed = $this->refundService->claimAndProcess($inquiry, $payMongo);
        } catch (\RuntimeException $e) {
            return redirect()->route('admin.inquiries.show', $inquiry)
                ->with('error', $e->getMessage());
        }

        if ($claimed !== RefundService::CLAIMED) {
            return redirect()->route('admin.inquiries.show', $inquiry)
                ->with('error', 'This booking has already been refunded.');
        }

        $inquiry->refresh();
        $wasConfirmed = $inquiry->status === Inquiry::STATUS_CONFIRMED;

        $inquiry->update([
            'status' => Inquiry::STATUS_CANCELLED,
            'refunded_at' => now(),
            'refund_amount' => $inquiry->refundableAmount(),
        ]);
        $inquiry->releaseBlocks();

        if ($wasConfirmed) {
            $inquiry->reverseStay();
        }

        DashboardController::forgetCache();

        $this->logger->record('inquiry.refunded', $inquiry, "Payment for {$inquiry->reference_code} refunded and booking cancelled.");

        try {
            Mail::to($inquiry->email)->send(new RefundReceived($inquiry));
        } catch (\Exception $e) {
            Log::error('Failed to send refund email', [
                'inquiry_id' => $inquiry->id,
                'error' => $e->getMessage(),
            ]);
        }

        return redirect()->route('admin.inquiries.show', $inquiry)
            ->with('success', "Payment for {$inquiry->reference_code} refunded and booking cancelled.");
    }
}
