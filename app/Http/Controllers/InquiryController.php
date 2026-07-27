<?php

namespace App\Http\Controllers;

use App\Http\Requests\InquiryRequest;
use App\Models\Cottage;
use App\Models\Inquiry;
use App\Services\InquiryService;

class InquiryController extends Controller
{
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

    public function store(InquiryRequest $request, InquiryService $inquiryService)
    {
        $inquiry = $inquiryService->store($request->validated());

        return redirect()->route('booking.confirmation', $inquiry);
    }

    public function show(Inquiry $inquiry)
    {
        return view('pages.confirmation', compact('inquiry'));
    }
}
