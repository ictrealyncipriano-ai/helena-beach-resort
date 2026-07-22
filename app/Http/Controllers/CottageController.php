<?php

namespace App\Http\Controllers;

use App\Models\Cottage;

class CottageController extends Controller
{
    public function index()
    {
        $cottages = Cottage::where('is_available', true)
            ->orderBy('sort_order')
            ->get();

        return view('pages.cottages.index', compact('cottages'));
    }

    public function show(Cottage $cottage)
    {
        $blockedDates = $cottage->dateBlocks()
            ->future()
            ->pluck('date')
            ->values();

        return view('pages.cottages.show', compact('cottage', 'blockedDates'));
    }
}
