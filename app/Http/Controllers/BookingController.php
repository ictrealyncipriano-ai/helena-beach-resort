<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookingRequest;
use App\Models\Cottage;
use App\Services\InquiryService;

class BookingController extends Controller
{
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

    public function store(BookingRequest $request, InquiryService $inquiryService)
    {
        $data = $request->validated();
        $data['source'] = 'booking';

        $inquiry = $inquiryService->store($data);

        return redirect()->route('booking.confirmation', $inquiry);
    }
}
