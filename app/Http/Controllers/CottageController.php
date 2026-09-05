<?php

namespace App\Http\Controllers;

use App\Models\Cottage;
use App\Support\PublicCache;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/**
 * Public-facing cottage listing and detail pages.
 */
class CottageController extends Controller
{
    /** List all available cottages */
    public function index(): View
    {
        $cottages = Cache::remember(PublicCache::COTTAGES_INDEX, PublicCache::CONTENT_TTL, function () {
            return Cottage::with('primaryPhoto:cottage_id,photo_path,is_primary')
                ->where('is_available', true)
                ->orderBy('sort_order')
                ->select('id', 'name', 'slug', 'description', 'capacity', 'rate_daytour', 'rate_overnight', 'sort_order', 'is_available')
                ->get();
        });

        return view('pages.cottages.index', compact('cottages'));
    }

    /** Show a single cottage with its future blocked dates (capped at 12 months) */
    public function show(Cottage $cottage): View
    {
        $cottage->load(['photos', 'amenities', 'primaryPhoto']);

        // Format to 'Y-m-d' so the JSON-encoded calendar data matches the
        // `YYYY-MM-DD` date strings built in the JS calendar component.
        // Capped at 12 months: beyond that the calendar never renders and
        // the unbounded pluck would grow with every future block.
        $blockedDates = $cottage->dateBlocks()
            ->whereBetween('date', [now()->startOfDay(), now()->addMonths(12)->endOfDay()])
            ->pluck('date')
            ->map(fn ($date) => $date->format('Y-m-d'))
            ->values();

        return view('pages.cottages.show', compact('cottage', 'blockedDates'));
    }
}
