<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\GuardsBookingAccess;
use App\Http\Requests\InquiryRequest;
use App\Models\Cottage;
use App\Models\Inquiry;
use App\Queries\BlockedDates;
use App\Services\InquiryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Handles general inquiries from the contact page.
 * Similar to BookingController but marks source as 'website'.
 */
class InquiryController extends Controller
{
    use GuardsBookingAccess;

    /** Show contact/inquiry form with cottage list and blocked dates */
    public function create(): View
    {
        $cottages = Cottage::available()
            ->select('id', 'name', 'capacity', 'sort_order', 'is_available')
            ->get();

        // Fetch blocked dates for every cottage in a single query instead of
        // one dateBlocks() query per cottage (N+1). Capped at 180 days like
        // the booking form.
        $blockedByCottage = BlockedDates::byCottage($cottages->pluck('id'));

        return view('pages.contact', compact('cottages', 'blockedByCottage'));
    }

    /** Store inquiry, create guest record, notify owner */
    public function store(InquiryRequest $request, InquiryService $inquiryService): RedirectResponse
    {
        $inquiry = $inquiryService->store($request->validated());

        // The guest just submitted this booking — allow them to see the
        // confirmation page in the same session.
        $this->grantBookingAccess($inquiry);

        return redirect()->route('booking.confirmation', $inquiry);
    }

    /** Show booking confirmation after submission */
    public function show(Inquiry $inquiry): View
    {
        $this->authorizeBookingAccess($inquiry);

        return view('pages.confirmation', compact('inquiry'));
    }
}
