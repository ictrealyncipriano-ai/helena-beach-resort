<?php

namespace App\Http\Controllers;

use App\Models\Cottage;

/**
 * Public-facing cottage listing and detail pages.
 */
class CottageController extends Controller
{
    /** List all available cottages */
    public function index()
    {
        $cottages = Cottage::with('primaryPhoto')
            ->where('is_available', true)
            ->orderBy('sort_order')
            ->get();

        return view('pages.cottages.index', compact('cottages'));
    }

    /** Show a single cottage with its future blocked dates */
    public function show(Cottage $cottage)
    {
        // Format to 'Y-m-d' so the JSON-encoded calendar data matches the
        // `YYYY-MM-DD` date strings built in the JS calendar component.
        $blockedDates = $cottage->dateBlocks()
            ->future()
            ->pluck('date')
            ->map(fn ($date) => $date->format('Y-m-d'))
            ->values();

        return view('pages.cottages.show', compact('cottage', 'blockedDates'));
    }
}
