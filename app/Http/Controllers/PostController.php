<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Support\PublicCache;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;

/**
 * Public news / announcements pages.
 */
class PostController extends Controller
{
    /** Paginated list of published posts */
    public function index()
    {
        $posts = $this->paginate(
            Cache::remember(PublicCache::POSTS_ALL, PublicCache::CONTENT_TTL, function () {
                return Post::active()->get();
            }),
            9
        );

        return view('pages.news.index', compact('posts'));
    }

    /** Single published article; unpublished/scheduled posts 404 */
    public function show(Post $post)
    {
        abort_unless($post->isPublished(), 404);

        return view('pages.news.show', compact('post'));
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
