<?php

namespace App\Http\Controllers;

use App\Models\Gallery;
use App\Support\PublicCache;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;

/**
 * Public photo gallery page with category filtering.
 */
class GalleryController extends Controller
{
    /** Show paginated gallery grouped by category */
    public function index()
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

    /**
     * Turn a cached collection into a paginator without caching the paginator
     * itself (paginator objects embed request state).
     */
    private function paginate($items, int $perPage)
    {
        $page = Paginator::resolveCurrentPage();
        $slice = $items->forPage($page, $perPage)->values();

        return new LengthAwarePaginator(
            $slice,
            $items->count(),
            $perPage,
            $page,
            ['path' => Paginator::resolveCurrentPath()]
        );
    }
}
