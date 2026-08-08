<?php

namespace Tests\Feature;

use App\Models\Cottage;
use App\Models\Faq;
use App\Models\Gallery;
use App\Models\Service;
use App\Models\Testimonial;
use App\Models\User;
use App\Support\PublicCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Phase 10 — public content pages cache their query results so repeat visits
 * do not re-run the expensive eager-loaded queries. Every edit to a content
 * model (cottage, photo, gallery, testimonial, FAQ, service) must flush the
 * caches immediately, including bulk actions that bypass model events.
 */
class PublicPageCacheTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Cache::flush();
    }

    public function test_home_page_populates_cache(): void
    {
        $this->get('/')->assertOk();

        $this->assertTrue(Cache::has(PublicCache::HOME));
    }

    public function test_faq_page_populates_cache(): void
    {
        $this->get('/faq')->assertOk();

        $this->assertTrue(Cache::has(PublicCache::FAQS_ALL));
    }

    public function test_services_page_populates_cache(): void
    {
        $this->get('/services')->assertOk();

        $this->assertTrue(Cache::has(PublicCache::SERVICES_ALL));
    }

    public function test_reviews_page_populates_cache(): void
    {
        $this->get('/reviews')->assertOk();

        $this->assertTrue(Cache::has(PublicCache::REVIEWS_ALL));
    }

    public function test_cottages_index_populates_cache(): void
    {
        $this->get('/cottages')->assertOk();

        $this->assertTrue(Cache::has(PublicCache::COTTAGES_INDEX));
    }

    public function test_gallery_page_populates_cache(): void
    {
        $this->get('/gallery')->assertOk();

        $this->assertTrue(Cache::has(PublicCache::GALLERY_ALL));
        $this->assertTrue(Cache::has(PublicCache::GALLERY_CATEGORIES));
    }

    public function test_saving_a_faq_flushes_public_cache(): void
    {
        $this->prime();

        $faq = Faq::first();
        $faq->update(['answer' => 'Updated answer.']);

        $this->assertFullyFlushed();
    }

    public function test_deleting_a_testimonial_flushes_public_cache(): void
    {
        $this->prime();

        Testimonial::first()->delete();

        $this->assertFullyFlushed();
    }

    public function test_saving_a_gallery_image_flushes_public_cache(): void
    {
        $this->prime();

        $gallery = Gallery::first();
        $gallery->update(['title' => 'Updated title']);
        $gallery->refresh();
        $gallery->delete();

        $this->assertFullyFlushed();
    }

    public function test_saving_a_service_flushes_public_cache(): void
    {
        $this->prime();

        Service::first()->update(['name' => 'Updated service']);

        $this->assertFullyFlushed();
    }

    public function test_saving_a_cottage_photo_flushes_public_cache(): void
    {
        $this->prime();

        Cottage::first()->photos()->create([
            'photo_path' => 'cottages/test.jpg',
            'is_primary' => false,
            'sort_order' => 99,
        ]);

        $this->assertFullyFlushed();
    }

    public function test_bulk_activate_faqs_flushes_public_cache(): void
    {
        $this->prime();

        $admin = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($admin)
            ->post(route('admin.faqs.activate-all'))
            ->assertRedirect(route('admin.faqs.index'));

        $this->assertFullyFlushed();
    }

    public function test_cottage_show_does_not_use_public_content_cache(): void
    {
        // Availability (date blocks) must stay real-time even though the
        // static cottage data itself is route-model-bound (single query).
        $cottage = Cottage::first();
        $this->get("/cottages/{$cottage->slug}")->assertOk();

        foreach ($this->contentKeys() as $key) {
            $this->assertFalse(Cache::has($key), "Cottage show must not populate {$key}.");
        }
    }

    /** Populate every public cache key via the public pages. */
    private function prime(): void
    {
        $this->get('/')->assertOk();
        $this->get('/faq')->assertOk();
        $this->get('/services')->assertOk();
        $this->get('/reviews')->assertOk();
        $this->get('/cottages')->assertOk();
        $this->get('/gallery')->assertOk();
    }

    /** The public-content cache keys. */
    private function contentKeys(): array
    {
        return [
            PublicCache::HOME,
            PublicCache::FAQS_ALL,
            PublicCache::SERVICES_ALL,
            PublicCache::REVIEWS_ALL,
            PublicCache::COTTAGES_INDEX,
            PublicCache::GALLERY_ALL,
            PublicCache::GALLERY_CATEGORIES,
        ];
    }

    /** Assert no public-content cache key survived. */
    private function assertFullyFlushed(): void
    {
        foreach ($this->contentKeys() as $key) {
            $this->assertFalse(Cache::has($key), "Expected cache key {$key} to be flushed.");
        }
    }
}
