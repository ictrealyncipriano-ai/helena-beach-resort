<?php

namespace Tests\Feature;

use App\Mail\BookingConfirmed;
use App\Mail\InquiryAcknowledgment;
use App\Models\Cottage;
use App\Models\Inquiry;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Phase 4 exit gate (WP-0): a fresh deployment (migrated but UNSEEDED,
 * neutral config) must present zero resort identity anywhere.
 *
 * Deliberately no $this->seed(): this is the "second resort just cloned
 * the repo" state. Each test FAILS while Helena defaults, hosts, or seed
 * assumptions leak into rendered output, and PASSES once Phase 4
 * neutralizes them. Binary placeholder obviousness is human-reviewed in
 * WP-4 (pixels cannot be unit-tested here).
 */
class ResortIdentityPhase4Test extends TestCase
{
    use RefreshDatabase;

    private const BANNED = ['Helena', 'Infanta', 'Quezon', 'PH-QUE', 'labcoop', 'onrender'];

    protected function setUp(): void
    {
        parent::setUp();

        // Fresh-deploy simulation: no seeders run, so drop any settings
        // memoized/cached by previously-executed test classes in this
        // process — a real fresh install boots with empty caches.
        SiteSetting::forgetCache();

        // Simulate a configured second-resort deploy: identity comes from
        // config + DB only. Banned strings must then come from code, never
        // from these values. The URL root is pinned because the generator
        // snapshots it from the environment, not from runtime config.
        config([
            'app.name' => 'Sunset Paradise Resort',
            'app.url' => 'https://sunset.example.com',
            'mail.from.address' => 'noreply@sunset.example.com',
            'mail.from.name' => 'Sunset Paradise Resort',
            'filesystems.disks.public.url' => 'https://sunset.example.com/storage',
        ]);
        URL::forceRootUrl('https://sunset.example.com');
        URL::forceScheme('https');
    }

    private function assertNeutral(string $html, string $context): void
    {
        foreach (self::BANNED as $needle) {
            $this->assertStringNotContainsString($needle, $html, "Banned string '{$needle}' rendered ({$context}).");
        }
    }

    public function test_public_pages_render_without_seed_data(): void
    {
        foreach (['/', '/about', '/contact', '/robots.txt', '/sitemap.xml'] as $path) {
            $this->get($path)->assertOk();
        }
    }

    public function test_fresh_deploy_contains_no_resort_identity(): void
    {
        foreach (['/', '/about', '/contact'] as $path) {
            $this->assertNeutral($this->get($path)->assertOk()->getContent(), "GET {$path}");
        }
    }

    public function test_robots_and_sitemap_carry_no_legacy_hosts(): void
    {
        $robots = $this->get('/robots.txt')->assertOk()->getContent();
        $sitemap = $this->get('/sitemap.xml')->assertOk()->getContent();

        $this->assertNeutral($robots, 'robots.txt');
        $this->assertNeutral($sitemap, 'sitemap.xml');
        $this->assertStringContainsString(rtrim(config('app.url'), '/').'/sitemap.xml', $robots);
    }

    public function test_emails_render_neutrally_without_seed_data(): void
    {
        $cottage = Cottage::forceCreate([
            'name' => 'Sample Cottage',
            'description' => 'Sample description.',
            'rate_daytour' => 1000,
            'rate_overnight' => 2000,
            'is_available' => true,
        ]);

        $inquiry = Inquiry::forceCreate([
            'name' => 'Sunset Guest',
            'email' => 'sunset@example.com',
            'booking_type' => Inquiry::TYPE_DAY_TOUR,
            'cottage_id' => $cottage->id,
            'check_in' => '2026-09-10',
            'status' => Inquiry::STATUS_PENDING,
            'total_amount' => '1500.00',
        ]);

        foreach ([
            (new BookingConfirmed($inquiry))->render(),
            (new InquiryAcknowledgment($inquiry))->render(),
        ] as $html) {
            $this->assertNeutral($html, 'transactional email');
        }
    }
}
