<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 3 exit gate (WP-0): remaining marketing/SEO copy and page shells
 * must derive from settings, not hardcoded resort identity.
 *
 * Simulates "Sunset Paradise Resort" (El Nido, Palawan). Each test FAILS on
 * the pre-Phase-3 codebase for the stated hardcoded-copy reason and PASSES
 * once Phase 3 wires the copy to settings.
 *
 * Out of scope here (asserted nowhere): palette/fonts, multi-tenancy,
 * per-page SEO key management (WP-3 derives copy, adds zero keys), seeded
 * testimonial/post copy (starter data, editable via admin).
 */
class ResortIdentityPhase3Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        config(['app.name' => 'Sunset Paradise Resort']);

        foreach ([
            'site_name' => 'Sunset Paradise Resort',
            'address' => 'White Beach, El Nido, Palawan',
            'contact_phone' => '+63 900 000 0000',
            'geo_placename' => 'El Nido, Palawan',
            'about_body' => '<p>Sunset story by the lagoon.</p>',
            'footer_tagline' => 'Sunset tagline for footer.',
            'operating_hours' => 'Daily: 9:00 AM - 5:00 PM',
        ] as $key => $value) {
            SiteSetting::updateOrCreate(['key' => $key], ['value' => $value, 'type' => 'textarea']);
        }
        SiteSetting::forgetCache();
    }

    public function test_about_page_renders_configured_copy(): void
    {
        $this->get('/about')
            ->assertOk()
            ->assertSee('Sunset story by the lagoon', false)
            ->assertSee('White Beach, El Nido, Palawan')
            ->assertSee('Daily: 9:00 AM - 5:00 PM')
            ->assertDontSee('Welcome to Helena Beach Resort')
            ->assertDontSee('Helena Beach Resort offers a peaceful retreat');
    }

    public function test_contact_page_renders_configured_copy(): void
    {
        $this->get('/contact')
            ->assertOk()
            ->assertSee('White Beach, El Nido, Palawan')
            ->assertSee('Daily: 9:00 AM - 5:00 PM')
            ->assertDontSee('Purok Buyan');
    }

    public function test_footer_renders_configured_copy(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Sunset tagline for footer.')
            ->assertDontSee('perfect getaway at Helena Beach Resort');
    }

    public function test_seo_copy_derives_from_settings(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Sunset Paradise Resort | Beachfront Cottages in El Nido, Palawan', false);

        $this->get('/about')
            ->assertOk()
            ->assertSee('Learn more about Sunset Paradise Resort', false);

        $this->get('/privacy')
            ->assertOk()
            ->assertSee('Privacy Policy — Sunset Paradise Resort, El Nido, Palawan', false);
    }

    public function test_legal_draft_warning_is_shown_until_replaced(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        // Simulate the seeder fallback path (legacy installs): with no rows
        // present the seeder inserts "Draft" copy, which must trigger a
        // non-blocking warning. (Fresh migrate+seed installs carry final
        // copy from the migration itself.)
        SiteSetting::whereIn('key', ['legal_privacy', 'legal_terms', 'legal_booking_policy'])->delete();
        (new \Database\Seeders\SiteSettingSeeder)->run();
        SiteSetting::forgetCache();

        $this->actingAs($admin)
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('still contain draft copy');

        // Replacing all three legal texts clears the warning.
        foreach (['legal_privacy', 'legal_terms', 'legal_booking_policy'] as $key) {
            SiteSetting::updateOrCreate(['key' => $key], ['value' => '<p>Final policy.</p>', 'type' => 'textarea']);
        }
        SiteSetting::forgetCache();

        $this->actingAs($admin)
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertDontSee('still contain draft copy');
    }
}
