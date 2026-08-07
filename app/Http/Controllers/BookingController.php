<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\GuardsBookingAccess;
use App\Http\Requests\BookingRequest;
use App\Models\Cottage;
use App\Models\CottageDateBlock;
use App\Models\Inquiry;
use App\Services\InquiryService;

/**
 * Handles the booking flow: show booking form and store new bookings.
 * Differs from InquiryController in that bookings have a 'booking' source tag.
 */
class BookingController extends Controller
{
    use GuardsBookingAccess;

    /**
     * Show booking form with available cottages, blocked dates, and rate info.
     */
    public function create()
    {
        $cottages = Cottage::where('is_available', true)
            ->orderBy('sort_order')
            ->get();

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
            ->where('date', '>=', today())
            ->select('cottage_id', 'date')
            ->get()
            ->groupBy('cottage_id')
            ->map(fn ($blocks) => $blocks->pluck('date')
                ->map(fn ($date) => $date->format('Y-m-d'))
                ->values());

        $rates = $cottages->mapWithKeys(fn ($c) => [
            $c->id => [
                'day_tour' => (float) $c->rate_daytour,
                'overnight' => (float) $c->rate_overnight,
                'name' => $c->name,
                'capacity' => $c->capacity,
            ],
        ]);

        return view('pages.book', compact('cottages', 'blockedByCottage', 'rates', 'prefillCottageId'));
    }

    /**
     * Store a new booking and redirect to confirmation page.
     */
    public function store(BookingRequest $request, InquiryService $inquiryService)
    {
        $data = $request->validated();
        $data['source'] = 'booking';

        // Server-side idempotency guard: a guest double-clicking (or a flaky
        // network retry) must not create a second pending request for the
        // same cottage + dates. Reuse the earlier one instead.
        $duplicate = Inquiry::query()
            ->where('email', $data['email'])
            ->where('status', 'pending')
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
            ->where('created_at', '>=', now()->subMinutes(10))
            ->latest('id')
            ->first();

        if ($duplicate) {
            // Re-grant access so the guest can land on the confirmation page
            // even if their previous session token has since expired.
            $this->grantBookingAccess($duplicate);

            return redirect()->route('booking.confirmation', $duplicate);
        }

        $inquiry = $inquiryService->store($data);

        // The guest just submitted this booking — allow them to see the
        // confirmation page in the same session.
        $this->grantBookingAccess($inquiry);

        return redirect()->route('booking.confirmation', $inquiry);
    }
}
