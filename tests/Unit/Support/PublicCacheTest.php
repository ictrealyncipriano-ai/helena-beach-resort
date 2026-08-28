<?php

namespace Tests\Unit\Support;

use App\Support\PublicCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Phase 9.5 — direct coverage that PublicCache::flush() drops every public
 * page key, including POSTS_ALL and SITEMAP which the feature suite only
 * asserted indirectly for the seven content keys.
 */
class PublicCacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_flush_drops_every_registered_key(): void
    {
        $keys = [
            PublicCache::HOME,
            PublicCache::COTTAGES_INDEX,
            PublicCache::FAQS_ALL,
            PublicCache::SERVICES_ALL,
            PublicCache::REVIEWS_ALL,
            PublicCache::GALLERY_ALL,
            PublicCache::GALLERY_CATEGORIES,
            PublicCache::POSTS_ALL,
            PublicCache::SITEMAP,
        ];

        foreach ($keys as $key) {
            Cache::put($key, 'value', 600);
        }

        PublicCache::flush();

        foreach ($keys as $key) {
            $this->assertFalse(Cache::has($key), "Expected cache key [$key] to be flushed.");
        }
    }

    public function test_constants_resolve_to_expected_keys(): void
    {
        $this->assertSame('pages.home', PublicCache::HOME);
        $this->assertSame('pages.posts.all', PublicCache::POSTS_ALL);
        $this->assertSame('sitemap', PublicCache::SITEMAP);
        $this->assertSame('pages.gallery.categories', PublicCache::GALLERY_CATEGORIES);
    }
}
