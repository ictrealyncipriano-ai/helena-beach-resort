<?php

namespace App\Http\Controllers;

use App\Http\Requests\InquiryRequest;
use App\Models\Cottage;
use App\Models\Inquiry;
use App\Services\InquiryService;

/**
 * Handles general inquiries from the contact page.
 * Similar to BookingController but marks source as 'website'.
 */
class InquiryController extends Controller
{
    /** Show contact/inquiry form with cottage list and blocked dates */
    public function create()
    {
        $cottages = Cottage::where('is_available', true)
            ->orderBy('sort_order')
            ->get();

        $blockedByCottage = $cottages->mapWithKeys(fn ($c) => [
            $c->id => $c->dateBlocks()->future()->pluck('date'),
        ]);

        return view('pages.contact', compact('cottages', 'blockedByCottage'));
    }

    /** Store inquiry, create guest record, notify owner */
    public function store(InquiryRequest $request, InquiryService $inquiryService)
    {
        $inquiry = $inquiryService->store($request->validated());

        return redirect()->route('booking.confirmation', $inquiry);
    }

    /** Show booking confirmation after submission */
    public function show(Inquiry $inquiry)
    {
        return view('pages.confirmation', compact('inquiry'));
    }
}
