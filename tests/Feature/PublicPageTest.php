<?php

namespace Tests\Feature;

use App\Models\Cottage;
use App\Models\CottageDateBlock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_home_page_returns_200(): void
    {
        $this->get('/')->assertStatus(200);
    }

    public function test_about_page_returns_200(): void
    {
        $this->get('/about')->assertStatus(200);
    }

    public function test_cottages_index_returns_200(): void
    {
        $this->get('/cottages')->assertStatus(200);
    }

    public function test_gallery_page_returns_200(): void
    {
        $this->get('/gallery')->assertStatus(200);
    }

    public function test_contact_page_returns_200(): void
    {
        $this->get('/contact')->assertStatus(200);
    }

    public function test_contact_page_with_date_blocks_returns_200(): void
    {
        $cottage = Cottage::first();
        CottageDateBlock::create([
            'cottage_id' => $cottage->id,
            'date' => now()->addDay()->format('Y-m-d'),
            'reason' => 'Test booking',
        ]);
        $this->get('/contact')->assertStatus(200);
    }

    public function test_sitemap_returns_xml(): void
    {
        $this->get('/sitemap.xml')
            ->assertStatus(200)
            ->assertHeader('Content-Type', 'application/xml');
    }

    public function test_sitemap_contains_all_static_pages(): void
    {
        $this->get('/sitemap.xml')
            ->assertSee('/about')
            ->assertSee('/cottages')
            ->assertSee('/gallery')
            ->assertSee('/contact');
    }

    public function test_cottage_show_returns_200_for_valid_slug(): void
    {
        $cottage = Cottage::first();
        $this->get("/cottages/{$cottage->slug}")->assertStatus(200);
    }

    public function test_cottage_show_with_date_blocks_returns_200(): void
    {
        $cottage = Cottage::first();
        CottageDateBlock::create([
            'cottage_id' => $cottage->id,
            'date' => now()->addDay()->format('Y-m-d'),
            'reason' => 'Test booking',
        ]);
        $this->get("/cottages/{$cottage->slug}")->assertStatus(200);
    }

    public function test_cottage_show_returns_404_for_invalid_slug(): void
    {
        $this->get('/cottages/nonexistent-cottage')->assertStatus(404);
    }

    public function test_nonexistent_route_returns_404(): void
    {
        $this->get('/nonexistent-page')->assertStatus(404);
    }

    public function test_home_page_has_keyword_rich_title_and_jsonld(): void
    {
        $this->get('/')
            ->assertSee('Helena Beach Resort | Beachfront Cottages in Infanta, Quezon')
            ->assertSee('LodgingBusiness')
            ->assertSee('aggregateRating');
    }

    public function test_cottage_show_has_canonical_and_product_jsonld(): void
    {
        $cottage = Cottage::first();
        $this->get("/cottages/{$cottage->slug}")
            ->assertSee('rel="canonical"', false)
            ->assertSee('"@type":"Product"', false)
            ->assertSee('"@type":"BreadcrumbList"', false);
    }

    public function test_robots_txt_route_reflects_app_url(): void
    {
        $this->get('/robots.txt')
            ->assertStatus(200)
            ->assertHeader('Content-Type', 'text/plain; charset=utf-8')
            ->assertSee('Sitemap: ' . rtrim(config('app.url'), '/') . '/sitemap.xml')
            ->assertSee('Disallow: /admin')
            ->assertDontSee('Disallow: /storage');
    }

    public function test_admin_layout_has_noindex(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($admin)
            ->get('/admin/dashboard')
            ->assertStatus(200)
            ->assertSee('noindex, nofollow');
    }

    public function test_sitemap_includes_lastmod(): void
    {
        $this->get('/sitemap.xml')
            ->assertStatus(200)
            ->assertSee('<lastmod>', false);
    }
}
