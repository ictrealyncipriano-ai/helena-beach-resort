<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsentBannerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_no_ga4_id_renders_no_tracking_and_no_banner(): void
    {
        $this->get('/')
            ->assertDontSee('googletagmanager.com/gtag/js')
            ->assertDontSee('helenaGa4Id')
            ->assertDontSee('We value your privacy');
    }

    public function test_ga4_id_renders_consent_mode_script_and_banner(): void
    {
        SiteSetting::where('key', 'analytics_ga4_id')->update(['value' => 'G-ABC123XYZ']);

        $this->get('/')
            ->assertSee('googletagmanager.com/gtag/js', false)
            ->assertSee('helenaGa4Id', false)
            ->assertSee('"G-ABC123XYZ"', false)
            ->assertSee('helenaConsentRequired = true', false)
            ->assertSee('We value your privacy')
            ->assertSee('Accept')
            ->assertSee('Decline')
            ->assertSee(route('privacy'), false);
    }

    public function test_consent_disabled_skips_banner_but_keeps_tracking(): void
    {
        SiteSetting::where('key', 'analytics_ga4_id')->update(['value' => 'G-ABC123XYZ']);
        SiteSetting::where('key', 'analytics_consent_enabled')->update(['value' => '0']);

        $this->get('/')
            ->assertSee('googletagmanager.com/gtag/js', false)
            ->assertSee('helenaConsentRequired = false', false)
            ->assertDontSee('We value your privacy');
    }

    public function test_consent_enabled_without_ga4_id_skips_banner(): void
    {
        SiteSetting::where('key', 'analytics_consent_enabled')->update(['value' => '1']);

        $this->get('/')
            ->assertDontSee('We value your privacy')
            ->assertDontSee('googletagmanager.com/gtag/js');
    }
}
