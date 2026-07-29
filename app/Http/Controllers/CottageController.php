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
        $cottages = Cottage::where('is_available', true)
            ->orderBy('sort_order')
            ->get();

        return view('pages.cottages.index', compact('cottages'));
    }

    /** Show a single cottage with its future blocked dates */
    public function show(Cottage $cottage)
    {
        $blockedDates = $cottage->dateBlocks()
            ->future()
            ->pluck('date')
            ->values();

        return view('pages.cottages.show', compact('cottage', 'blockedDates'));
    }
}
