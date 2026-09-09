<?php

namespace Tests\Feature;

use App\Models\Cottage;
use App\Models\Inquiry;
use App\Models\SiteSetting;
use App\Services\BookingEligibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 2 exit gate (WP-0): second-resort identity, geo, and booking rules
 * must come from settings, not source code.
 *
 * Simulates "Sunset Paradise Resort" (El Nido, Palawan) with custom rules:
 * 72-hour modification cutoff, 72-hour hold window, SP- prefix values are
 * covered by Phase 1. Each test FAILS on the pre-Phase-2 codebase for the
 * stated missing-config reason and PASSES once Phase 2 is complete.
 *
 * Out of scope here (asserted nowhere): page-specific SEO titles/meta,
 * footer marketing description, seeded testimonial/post copy, palette/fonts.
 */
class ResortIdentityPhase2Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        config(['app.name' => 'Sunset Paradise Resort']);
        SiteSetting::updateOrCreate(['key' => 'site_name'], ['value' => 'Sunset Paradise Resort', 'type' => 'text']);
        SiteSetting::forgetCache();
    }

    private function sampleInquiry(array $overrides = []): Inquiry
    {
        return Inquiry::forceCreate(array_merge([
            'name' => 'Sunset Guest',
            'email' => 'sunset@example.com',
            'booking_type' => Inquiry::TYPE_DAY_TOUR,
            'cottage_id' => Cottage::first()->id,
            'check_in' => '2026-09-10',
            'status' => Inquiry::STATUS_PENDING,
            'total_amount' => '1500.00',
        ], $overrides));
    }

    public function test_logo_uses_configured_setting(): void
    {
        SiteSetting::updateOrCreate(['key' => 'site_logo'], ['value' => 'logos/sunset.png', 'type' => 'image']);
        SiteSetting::forgetCache();

        // Brand imagery must come from the setting; the shipped logo.jpg is
        // only a fallback when no logo is configured.
        $this->get('/')
            ->assertOk()
            ->assertSee('logos/sunset.png', false);
    }

    public function test_theme_color_and_favicon_use_settings(): void
    {
        SiteSetting::updateOrCreate(['key' => 'theme_color'], ['value' => '#123456', 'type' => 'text']);
        SiteSetting::updateOrCreate(['key' => 'site_favicon'], ['value' => 'favicons/sunset.ico', 'type' => 'image']);
        SiteSetting::forgetCache();

        $this->get('/')
            ->assertOk()
            ->assertSee('#123456', false)
            ->assertSee('favicons/sunset.ico', false)
            ->assertDontSee('#0f766e', false);
    }

    public function test_geo_and_schema_use_settings(): void
    {
        SiteSetting::updateOrCreate(['key' => 'geo_region'], ['value' => 'PH-PLW', 'type' => 'text']);
        SiteSetting::updateOrCreate(['key' => 'geo_placename'], ['value' => 'El Nido, Palawan', 'type' => 'text']);
        SiteSetting::updateOrCreate(['key' => 'address_locality'], ['value' => 'El Nido', 'type' => 'text']);
        SiteSetting::updateOrCreate(['key' => 'address_region'], ['value' => 'Palawan', 'type' => 'text']);
        SiteSetting::updateOrCreate(['key' => 'address_country'], ['value' => 'PH', 'type' => 'text']);
        SiteSetting::forgetCache();

        $this->get('/')
            ->assertOk()
            ->assertSee('PH-PLW', false)
            ->assertSee('El Nido, Palawan', false)
            ->assertSee('"addressLocality":"El Nido"', false)
            ->assertDontSee('geo.placename" content="Infanta, Quezon', false);
    }

    public function test_confirmation_hold_copy_reflects_setting(): void
    {
        SiteSetting::updateOrCreate(['key' => 'booking_hold_hours'], ['value' => '72', 'type' => 'text']);
        SiteSetting::forgetCache();

        $html = view('pages.confirmation', ['inquiry' => $this->sampleInquiry()])->render();

        $this->assertStringContainsString('72 hours', $html);
        $this->assertStringNotContainsString('48 hours', $html);
    }

    public function test_cutoff_rules_and_messages_reflect_setting(): void
    {
        SiteSetting::updateOrCreate(['key' => 'booking_cutoff_hours'], ['value' => '72', 'type' => 'text']);
        SiteSetting::forgetCache();

        // 36h out: inside a 72h cutoff (blocked) but outside the legacy 24h
        // cutoff (would be allowed) — separates new behavior from old.
        $inquiry = $this->sampleInquiry([
            'check_in' => '2026-08-17',
            'status' => Inquiry::STATUS_CONFIRMED,
        ]);

        $eligibility = app(BookingEligibility::class);

        $this->assertFalse($eligibility->canModify($inquiry));
        $this->assertStringContainsString('72 hours', (string) $eligibility->cannotModifyReason($inquiry));
        $this->assertFalse($eligibility->canCancel($inquiry));
    }

    public function test_hold_default_reflects_setting_with_explicit_override(): void
    {
        SiteSetting::updateOrCreate(['key' => 'booking_hold_hours'], ['value' => '72', 'type' => 'text']);
        SiteSetting::forgetCache();

        // 50h old: expired under the legacy 48h default, held under 72h.
        $inquiry = $this->sampleInquiry(['status' => Inquiry::STATUS_PENDING]);
        $inquiry->forceFill(['created_at' => now()->subHours(50)])->save();

        $this->artisan('reservations:release-expired');
        $this->assertSame(Inquiry::STATUS_PENDING, $inquiry->refresh()->status);

        // Explicit invocation still wins over the DB setting.
        $this->artisan('reservations:release-expired', ['--hours' => 48]);
        $this->assertSame(Inquiry::STATUS_EXPIRED, $inquiry->refresh()->status);
    }

    public function test_consent_namespace_has_no_helena_leftovers(): void
    {
        SiteSetting::where('key', 'analytics_ga4_id')->update(['value' => 'G-TEST12345']);
        SiteSetting::forgetCache();

        // Phase 1's temporary aliases are gone; only the resort namespace
        // remains (one-time re-prompt for legacy-cookie holders accepted).
        $this->get('/')
            ->assertOk()
            ->assertSee('resort_consent', false)
            ->assertSee('loadResortGtm', false)
            ->assertDontSee('helena_consent', false)
            ->assertDontSee('loadHelenaGtm', false)
            ->assertDontSee('helenaGa4Id', false);
    }

    public function test_missing_settings_fall_back_neutrally(): void
    {
        SiteSetting::whereIn('key', [
            'site_name', 'site_description', 'address', 'hero_heading',
            'hero_subtitle', 'section_reviews_subtitle', 'contact_email',
        ])->delete();
        SiteSetting::forgetCache();

        // Unseeded installs must never render another resort's identity in
        // composer-driven spots (config app.name is the neutral fallback).
        $this->get('/')
            ->assertOk()
            ->assertSee('"name":"Sunset Paradise Resort"', false)
            ->assertSee('info@example.com', false)
            ->assertDontSee('>Helena Beach<', false);
    }
}
