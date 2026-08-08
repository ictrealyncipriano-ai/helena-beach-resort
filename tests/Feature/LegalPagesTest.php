<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_privacy_policy_page_renders_content(): void
    {
        SiteSetting::updateOrCreate(['key' => 'legal_privacy'], [
            'value' => '<h2>Privacy</h2><p>We keep your data safe.</p><script>alert(1)</script>',
            'type' => 'textarea',
        ]);

        $this->get(route('privacy'))
            ->assertOk()
            ->assertSee('Privacy Policy')
            ->assertSee('We keep your data safe.')
            ->assertDontSee('<script>alert(1)');
    }

    public function test_terms_page_renders_content(): void
    {
        SiteSetting::updateOrCreate(['key' => 'legal_terms'], [
            'value' => '<p>These are the terms.</p>',
            'type' => 'textarea',
        ]);

        $this->get(route('terms'))
            ->assertOk()
            ->assertSee('Terms')
            ->assertSee('These are the terms.');
    }

    public function test_booking_policy_page_renders_content(): void
    {
        SiteSetting::updateOrCreate(['key' => 'legal_booking_policy'], [
            'value' => '<p>Check-in after 2 PM.</p>',
            'type' => 'textarea',
        ]);

        $this->get(route('booking-policy'))
            ->assertOk()
            ->assertSee('Booking Policy')
            ->assertSee('Check-in after 2 PM.');
    }

    public function test_legal_page_renders_when_setting_is_missing(): void
    {
        $this->get(route('privacy'))
            ->assertOk()
            ->assertSee('Privacy Policy');
    }

    public function test_footer_lists_legal_links(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee(route('privacy'), false)
            ->assertSee(route('terms'), false)
            ->assertSee(route('booking-policy'), false);
    }

    public function test_sitemap_includes_legal_pages(): void
    {
        $this->get(route('sitemap'))
            ->assertOk()
            ->assertSee(route('privacy'), false)
            ->assertSee(route('terms'), false)
            ->assertSee(route('booking-policy'), false);
    }
}
