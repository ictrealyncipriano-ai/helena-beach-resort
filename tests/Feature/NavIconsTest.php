<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Nav icon slice — every primary nav link (desktop + mobile drawer, including
 * My Booking) renders its icon as inline SVG next to the text label, and the
 * favicon set serves the shipped branded icons unless the admin configured a
 * custom site_favicon override.
 */
class NavIconsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_desktop_nav_links_render_icons_beside_labels(): void
    {
        $html = $this->get('/')->getContent();

        foreach (['Home', 'About', 'Cottages', 'Gallery', 'Services', 'FAQ', 'Reviews', 'News', 'Contact', 'My Booking'] as $label) {
            $this->assertStringContainsString("<span>{$label}</span>", $html);
        }

        // The header (desktop navbar + mobile drawer) carries an inline SVG
        // icon next to each labelled link: 9 primary links + My Booking in
        // both menus, plus menu/close/theme affordances.
        preg_match('/<header>.*<\/header>/s', $html, $m);
        $this->assertNotEmpty($m);
        $this->assertGreaterThanOrEqual(20, substr_count($m[0], '<svg'));

        // The mobile drawer keeps the same labels with icons.
        $this->assertStringContainsString('id="mobile-menu-drawer"', $html);
    }

    public function test_nav_icons_are_aria_hidden_with_visible_text(): void
    {
        $html = $this->get('/')->getContent();

        // The x-icons component marks decorative SVGs aria-hidden so screen
        // readers announce only the adjacent text label.
        $this->assertStringContainsString('aria-hidden="true"', $html);
        $this->assertStringContainsString('<span>Home</span>', $html);
    }

    public function test_default_favicon_set_serves_svg_plus_ico_fallback(): void
    {
        $html = $this->get('/')->getContent();

        $this->assertStringContainsString('rel="icon" type="image/svg+xml"', $html);
        $this->assertStringContainsString('favicon.svg', $html);
        $this->assertStringContainsString('favicon.ico', $html);
        $this->assertStringContainsString('apple-touch-icon.png', $html);
    }

    public function test_custom_favicon_override_suppresses_default_set(): void
    {
        SiteSetting::updateOrCreate(
            ['key' => 'site_favicon'],
            ['value' => 'favicons/custom.ico', 'type' => 'image']
        );
        SiteSetting::forgetCache();

        $html = $this->get('/')->getContent();

        $this->assertStringContainsString('favicons/custom.ico', $html);
        $this->assertStringNotContainsString('favicon.svg', $html);
    }
}
