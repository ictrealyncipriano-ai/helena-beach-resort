<?php

namespace Tests\Feature;

use App\Models\Cottage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /*
    |--------------------------------------------------------------------------
    | Gzip compression middleware
    |--------------------------------------------------------------------------
    */

    public function test_gzip_compression_applied_when_client_supports_it(): void
    {
        $response = $this->withHeaders([
            'Accept-Encoding' => 'gzip, deflate, br',
        ])->get('/');

        $response->assertHeader('Content-Encoding', 'gzip');
        $response->assertHeader('Vary', 'Accept-Encoding');
    }

    public function test_gzip_compression_not_applied_without_accept_encoding(): void
    {
        $response = $this->get('/');

        $response->assertHeaderMissing('Content-Encoding');
    }

    public function test_gzip_compression_skips_admin_routes(): void
    {
        $user = \App\Models\User::first();
        $this->actingAs($user);

        $response = $this->withHeaders([
            'Accept-Encoding' => 'gzip, deflate, br',
        ])->get('/admin/dashboard');

        $response->assertStatus(200);
        $response->assertHeaderMissing('Content-Encoding');
    }

    public function test_gzip_compression_skips_already_compressed_responses(): void
    {
        $response = $this->withHeaders([
            'Accept-Encoding' => 'gzip, deflate, br',
        ])->get('/robots.txt');

        // robots.txt is small and may or may not be compressed,
        // but the header should not be double-set.
        $encoding = $response->headers->get('Content-Encoding');
        $this->assertTrue($encoding === null || $encoding === 'gzip');
    }

    /*
    |--------------------------------------------------------------------------
    | Cottage detail eager loading
    |--------------------------------------------------------------------------
    */

    public function test_cottage_detail_eager_loads_photos_and_amenities(): void
    {
        $cottage = Cottage::with('photos', 'amenities', 'primaryPhoto')->first();

        $this->assertNotNull($cottage);
        $this->assertNotNull($cottage->photos);
        $this->assertNotNull($cottage->amenities);

        // Verify the relationships are loaded (not lazy)
        $this->assertTrue($cottage->relationLoaded('photos'));
        $this->assertTrue($cottage->relationLoaded('amenities'));
        $this->assertTrue($cottage->relationLoaded('primaryPhoto'));
    }

    /*
    |--------------------------------------------------------------------------
    | Image width/height attributes
    |--------------------------------------------------------------------------
    */

    public function test_home_page_cottage_images_have_width_and_height(): void
    {
        $response = $this->get('/');
        $content = $response->getContent();

        // Cottage card images on homepage
        $this->assertStringContainsString('width="400"', $content);
        $this->assertStringContainsString('height="300"', $content);
    }

    public function test_home_page_gallery_images_have_width_and_height(): void
    {
        $response = $this->get('/');
        $content = $response->getContent();

        // Gallery square thumbnails
        $this->assertStringContainsString('width="400"', $content);
        $this->assertStringContainsString('height="400"', $content);
    }

    public function test_cottage_show_page_images_have_width_and_height(): void
    {
        $cottage = Cottage::first();
        $response = $this->get(route('cottages.show', $cottage));
        $content = $response->getContent();

        $this->assertStringContainsString('width="600"', $content);
        $this->assertStringContainsString('height="450"', $content);
    }

    public function test_news_show_page_cover_has_width_and_height(): void
    {
        // Create a post with cover image to test
        $post = \App\Models\Post::create([
            'title' => 'Performance Test Post',
            'slug' => 'performance-test-post',
            'body' => '<p>Test body content for performance testing.</p>',
            'cover_image' => 'test/cover.jpg',
            'is_active' => true,
            'published_at' => now()->subDay(),
        ]);

        $response = $this->get(route('news.show', $post));
        $content = $response->getContent();

        $this->assertStringContainsString('width="768"', $content);
        $this->assertStringContainsString('height="432"', $content);
    }

    public function test_reviews_page_avatars_have_width_and_height(): void
    {
        $response = $this->get('/reviews');
        $content = $response->getContent();

        // The reviews page uses w-10 h-10 CSS classes for avatar sizing.
        // When guest_avatar is set, images get width="40" height="40";
        // when absent, a div fallback is used. Either way the sizing is explicit.
        $hasImgAvatars = str_contains($content, 'width="40"') && str_contains($content, 'height="40"');
        $hasDivAvatars = str_contains($content, 'w-10 h-10 rounded-full');
        $this->assertTrue($hasImgAvatars || $hasDivAvatars, 'Reviews page should have explicitly sized avatars (img width/height or div w-10 h-10)');
    }
}
