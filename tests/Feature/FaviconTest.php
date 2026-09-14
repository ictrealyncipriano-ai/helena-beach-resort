<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Branded favicon set — the default icon links serve the shipped SVG plus
 * .ico fallback, while an admin-configured site_favicon override keeps
 * rendering exactly the custom icon instead.
 */
class FaviconTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
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
