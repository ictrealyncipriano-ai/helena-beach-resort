<?php

namespace App\Http\Controllers;

use App\Models\Gallery;
use App\Support\PublicCache;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/**
 * Public photo gallery page with category filtering.
 */
class GalleryController extends Controller
{
    /** Show paginated gallery grouped by category */
    public function index(): View
    {
        $categories = Cache::remember(PublicCache::GALLERY_CATEGORIES, PublicCache::CONTENT_TTL, function () {
            return Gallery::where('is_active', true)
                ->select('category')
                ->distinct()
                ->pluck('category');
        });

        $galleries = $this->paginate(
            Cache::remember(PublicCache::GALLERY_ALL, PublicCache::CONTENT_TTL, function () {
                return Gallery::where('is_active', true)
                    ->orderBy('sort_order')
                    ->get();
            }),
            20
        );

        return view('pages.gallery.index', compact('galleries', 'categories'));
    }
}
