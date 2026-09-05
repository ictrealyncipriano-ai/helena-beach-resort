<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Support\PublicCache;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/**
 * Public news / announcements pages.
 */
class PostController extends Controller
{
    /** Paginated list of published posts */
    public function index(): View
    {
        $page = max(1, (int) request()->query('page', 1));
        $posts = Cache::remember(
            PublicCache::POSTS_ALL.".page.{$page}",
            PublicCache::CONTENT_TTL,
            fn () => Post::active()
                ->select('id', 'title', 'slug', 'excerpt', 'cover_image', 'published_at')
                ->paginate(9)
                ->withQueryString()
        );

        if ($posts->currentPage() !== $page) {
            $posts = Post::active()
                ->select('id', 'title', 'slug', 'excerpt', 'cover_image', 'published_at')
                ->paginate(9)
                ->withQueryString();
        }

        return view('pages.news.index', compact('posts'));
    }

    /** Single published article; unpublished/scheduled posts 404 */
    public function show(Post $post): View
    {
        abort_unless($post->isPublished(), 404);

        return view('pages.news.show', compact('post'));
    }
}
