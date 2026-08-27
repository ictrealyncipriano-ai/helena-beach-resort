<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\GuardsBookingAccess;
use App\Http\Requests\InquiryRequest;
use App\Models\Cottage;
use App\Models\CottageDateBlock;
use App\Models\Inquiry;
use App\Services\InquiryService;

/**
 * Handles general inquiries from the contact page.
 * Similar to BookingController but marks source as 'website'.
 */
class InquiryController extends Controller
{
    use GuardsBookingAccess;

    /** Show contact/inquiry form with cottage list and blocked dates */
    public function create()
    {
        $cottages = Cottage::available()->get();

        // Fetch blocked dates for every cottage in a single query instead of
        // one dateBlocks() query per cottage (N+1). Dates are formatted to
        // 'Y-m-d' so the data-blocked attributes on the <option> elements
        // match the date-string parsing in the page's JS (d.split('-')).
        $blockedByCottage = CottageDateBlock::whereIn('cottage_id', $cottages->pluck('id'))
            ->future()
            ->select('cottage_id', 'date')
            ->get()
            ->groupBy('cottage_id')
            ->map(fn ($blocks) => $blocks->pluck('date')
                ->map(fn ($date) => $date->format('Y-m-d'))
                ->values());

        return view('pages.contact', compact('cottages', 'blockedByCottage'));
    }

    /** Store inquiry, create guest record, notify owner */
    public function store(InquiryRequest $request, InquiryService $inquiryService)
    {
        $inquiry = $inquiryService->store($request->validated());

        // The guest just submitted this booking — allow them to see the
        // confirmation page in the same session.
        $this->grantBookingAccess($inquiry);

        return redirect()->route('booking.confirmation', $inquiry);
    }

    /** Show booking confirmation after submission */
    public function show(Inquiry $inquiry)
    {
        $this->authorizeBookingAccess($inquiry);

        return view('pages.confirmation', compact('inquiry'));
    }
}
