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

        $page = max(1, (int) request()->query('page', 1));
        $galleries = Cache::remember(
            PublicCache::GALLERY_ALL.".page.{$page}",
            PublicCache::CONTENT_TTL,
            fn () => Gallery::where('is_active', true)
                ->orderBy('sort_order')
                ->select('id', 'title', 'photo_path', 'category', 'sort_order')
                ->paginate(20)
                ->withQueryString()
        );

        // Paginator resolved outside cache context would lose the page; the
        // cached paginator already carries the correct page/path.
        if ($galleries->currentPage() !== $page) {
            $galleries = Gallery::where('is_active', true)
                ->orderBy('sort_order')
                ->select('id', 'title', 'photo_path', 'category', 'sort_order')
                ->paginate(20)
                ->withQueryString();
        }

        return view('pages.gallery.index', compact('galleries', 'categories'));
    }
}
