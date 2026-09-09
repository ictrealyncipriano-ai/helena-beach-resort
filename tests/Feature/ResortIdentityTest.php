<?php

namespace Tests\Feature;

use App\Mail\BookingConfirmed;
use App\Mail\InquiryAcknowledgment;
use App\Models\Cottage;
use App\Models\Inquiry;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 1 exit gate (WP-0): second-resort identity must come from
 * configuration/data, not source code.
 *
 * Simulates "Sunset Paradise Resort" taking over this codebase with only
 * .env + DB/admin settings changed. Each test FAILS on the Helena-hardcoded
 * codebase and PASSES once Phase 1 is complete.
 *
 * Deliberately scoped to Phase 1 (identity chrome, email sign-offs, invoice
 * closing line, booking prefix, consent namespace). Phase 2 content
 * (about/contact body copy, footer description, SEO/geo) is NOT asserted
 * here and remains allowed to contain Helena seed copy.
 */
class ResortIdentityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        config(['app.name' => 'Sunset Paradise Resort']);
        SiteSetting::updateOrCreate(
            ['key' => 'site_name'],
            ['value' => 'Sunset Paradise Resort', 'type' => 'text']
        );
        SiteSetting::forgetCache();
    }

    private function sampleInquiry(): Inquiry
    {
        return Inquiry::forceCreate([
            'name' => 'Sunset Guest',
            'email' => 'sunset@example.com',
            'booking_type' => Inquiry::TYPE_DAY_TOUR,
            'cottage_id' => Cottage::first()->id,
            'check_in' => '2026-09-10',
            'status' => Inquiry::STATUS_PENDING,
            'total_amount' => '1500.00',
        ]);
    }

    public function test_public_navbar_uses_configured_resort_name(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Sunset Paradise Resort')
            ->assertDontSee('>Helena Beach<', false);
    }

    public function test_admin_chrome_uses_app_name(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertDontSee('Helena Beach Resort')
            ->assertDontSee('>Helena Beach<', false);

        $admin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($admin)
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('Sunset Paradise Resort')
            ->assertDontSee('Helena Beach Admin')
            ->assertDontSee('Helena Beach Resort. All rights reserved.');
    }

    public function test_booking_emails_sign_with_configured_name(): void
    {
        $inquiry = $this->sampleInquiry();

        foreach ([
            (new BookingConfirmed($inquiry))->render(),
            (new InquiryAcknowledgment($inquiry))->render(),
        ] as $html) {
            $this->assertStringNotContainsString('Helena Beach Resort Team', $html);
            $this->assertStringContainsString('Sunset Paradise Resort Team', $html);
        }
    }

    public function test_invoice_closing_line_uses_configured_name(): void
    {
        $inquiry = $this->sampleInquiry();

        $html = view('pages.invoice', [
            'inquiry' => $inquiry,
            'items' => [[
                'desc' => 'Day Tour',
                'qty' => 1,
                'rate' => '1500.00',
                'total' => '1500.00',
            ]],
            'subtotal' => '1500.00',
        ])->render();

        $this->assertStringNotContainsString('Thank you for choosing Helena Beach Resort!', $html);
        $this->assertStringContainsString('Thank you for choosing Sunset Paradise Resort!', $html);
    }

    public function test_reference_codes_use_configured_prefix(): void
    {
        config(['booking.reference_prefix' => 'SP-']);

        $this->assertStringStartsWith('SP-', Inquiry::generateReferenceCode());
    }

    public function test_consent_uses_resort_namespace(): void
    {
        // The consent/tracking script only renders when a GA4 ID is configured.
        SiteSetting::where('key', 'analytics_ga4_id')->update(['value' => 'G-TEST12345']);
        SiteSetting::forgetCache();

        $this->get('/')
            ->assertOk()
            ->assertSee('resort_consent', false)
            ->assertSee('loadResortGtm', false);
    }
}
