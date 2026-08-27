<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\GuardsBookingAccess;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Requests\BookingRequest;
use App\Models\Cottage;
use App\Models\CottageDateBlock;
use App\Models\Inquiry;
use App\Services\InquiryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Handles the booking flow: show booking form and store new bookings.
 * Differs from InquiryController in that bookings have a 'booking' source tag.
 */
class BookingController extends Controller
{
    use GuardsBookingAccess;

    private const DEDUP_WINDOW_MINUTES = 10;

    /**
     * Show booking form with available cottages, blocked dates, and rate info.
     */
    public function create(): View
    {
        $cottages = Cottage::available()->get();

        // Prefill only when the requested cottage is actually available. An
        // unavailable cottage (or a non-existent one) silently clears the
        // prefill instead of leaving an empty "—" summary on the page.
        $prefillCottageId = null;
        if (request('cottage_id') && $cottages->contains('id', (int) request('cottage_id'))) {
            $prefillCottageId = (int) request('cottage_id');
        }

        // Fetch blocked dates for every cottage in a single query instead of
        // one dateBlocks() query per cottage (N+1). Dates are formatted to
        // 'Y-m-d' so the @js() output matches the flatpickr disable[] strings
        // and the 'Y-m-d' dateFormat used on the inputs.
        $blockedByCottage = CottageDateBlock::whereIn('cottage_id', $cottages->pluck('id'))
            ->future()
            ->select('cottage_id', 'date')
            ->get()
            ->groupBy('cottage_id')
            ->map(fn ($blocks) => $blocks->pluck('date')
                ->map(fn ($date) => $date->format('Y-m-d'))
                ->values());

        $rates = Cottage::ratesMap($cottages);

        return view('pages.book', compact('cottages', 'blockedByCottage', 'rates', 'prefillCottageId'));
    }

    /**
     * Store a new booking and redirect to confirmation page.
     */
    public function store(BookingRequest $request, InquiryService $inquiryService): RedirectResponse
    {
        $data = $request->validated();
        $data['source'] = Inquiry::SOURCE_BOOKING;

        // Session marker of inquiry ids this session created, so the
        // idempotency guard below only reuses a booking this requester
        // actually made. A spoofed email must never absorb another person's
        // pending booking.
        $created = session('booking_created_inquiries', []);
        if (! is_array($created)) {
            $created = [];
        }

        // Server-side idempotency guard: a guest double-clicking (or a flaky
        // network retry) must not create a second pending request for the
        // same cottage + dates. Reuse the earlier one instead.
        $duplicate = Inquiry::query()
            ->where('email', $data['email'])
            ->where('status', Inquiry::STATUS_PENDING)
            ->where('booking_type', $data['booking_type'] ?? null)
            ->where('cottage_id', $data['cottage_id'] ?? null)
            ->when(
                ! empty($data['check_in']),
                fn ($q) => $q->whereDate('check_in', $data['check_in']),
                fn ($q) => $q->whereNull('check_in')
            )
            ->when(
                ! empty($data['check_out']),
                fn ($q) => $q->whereDate('check_out', $data['check_out']),
                fn ($q) => $q->whereNull('check_out')
            )
            ->where('created_at', '>=', now()->subMinutes(self::DEDUP_WINDOW_MINUTES))
            ->latest('id')
            ->first();

        if ($duplicate && in_array($duplicate->id, $created, true)) {
            // Re-grant access so the guest can land on the confirmation page
            // even if their previous session token has since expired.
            $this->grantBookingAccess($duplicate);

            return redirect()->route('booking.confirmation', $duplicate);
        }

        $inquiry = $inquiryService->store($data);

        // A new pending booking changes the dashboard's pending count, so drop
        // the cached stats block like the other write paths do.
        DashboardController::forgetCache();

        $created[] = $inquiry->id;
        session(['booking_created_inquiries' => $created]);

        // The guest just submitted this booking — allow them to see the
        // confirmation page in the same session.
        $this->grantBookingAccess($inquiry);

        return redirect()->route('booking.confirmation', $inquiry);
    }
}
