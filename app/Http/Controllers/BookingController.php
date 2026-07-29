<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookingRequest;
use App\Models\Cottage;
use App\Services\InquiryService;

/**
 * Handles the booking flow: show booking form and store new bookings.
 * Differs from InquiryController in that bookings have a 'booking' source tag.
 */
class BookingController extends Controller
{
    /**
     * Show booking form with available cottages, blocked dates, and rate info.
     */
    public function create()
    {
        $cottages = Cottage::where('is_available', true)
            ->orderBy('sort_order')
            ->get();

        $blockedByCottage = $cottages->mapWithKeys(fn ($c) => [
            $c->id => $c->dateBlocks()->future()->pluck('date'),
        ]);

        $rates = $cottages->mapWithKeys(fn ($c) => [
            $c->id => [
                'day_tour' => (float) $c->rate_daytour,
                'overnight' => (float) $c->rate_overnight,
                'name' => $c->name,
                'capacity' => $c->capacity,
            ],
        ]);

        return view('pages.book', compact('cottages', 'blockedByCottage', 'rates'));
    }

    /**
     * Store a new booking and redirect to confirmation page.
     */
    public function store(BookingRequest $request, InquiryService $inquiryService)
    {
        $data = $request->validated();
        $data['source'] = 'booking';

        $inquiry = $inquiryService->store($data);

        return redirect()->route('booking.confirmation', $inquiry);
    }
}
