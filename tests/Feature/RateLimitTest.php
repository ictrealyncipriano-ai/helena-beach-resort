<?php

namespace Tests\Feature;

use App\Models\Cottage;
use App\Models\Inquiry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /*
    |--------------------------------------------------------------------------
    | Public write routes — 3 req/min
    |--------------------------------------------------------------------------
    */

    public function test_contact_form_rate_limited_after_3_requests(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->post('/contact', [
                'name' => "User {$i}",
                'email' => "user{$i}@example.com",
                'message' => 'Test message',
            ])->assertStatus(302);
        }

        $this->post('/contact', [
            'name' => 'Blocked User',
            'email' => 'blocked@example.com',
            'message' => 'Should be blocked',
        ])->assertStatus(429);
    }

    public function test_booking_store_rate_limited_after_3_requests(): void
    {
        $cottage = Cottage::first();

        for ($i = 0; $i < 3; $i++) {
            $day = 10 + $i;
            $this->post('/book', [
                'name' => "Guest {$i}",
                'email' => "guest{$i}@example.com",
                'booking_type' => 'overnight',
                'cottage_id' => $cottage->id,
                'check_in' => "2026-09-{$day}",
                'check_out' => "2026-09-".($day + 1),
                'pax' => 2,
            ])->assertStatus(302);
        }

        $this->post('/book', [
            'name' => 'Blocked',
            'email' => 'blocked@example.com',
            'booking_type' => 'overnight',
            'cottage_id' => $cottage->id,
            'check_in' => '2026-09-20',
            'check_out' => '2026-09-21',
            'pax' => 2,
        ])->assertStatus(429);
    }

    /*
    |--------------------------------------------------------------------------
    | Guest booking portal — lookup, cancel, review, modify
    |--------------------------------------------------------------------------
    */

    public function test_booking_lookup_rate_limited_after_5_requests(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/booking/lookup', [
                'email' => "lookup{$i}@example.com",
                'reference_code' => 'HB-NOPE',
            ])->assertStatus(302);
        }

        $this->post('/booking/lookup', [
            'email' => 'blocked@example.com',
            'reference_code' => 'HB-BLOCKED',
        ])->assertStatus(429);
    }

    public function test_cancel_rate_limited_after_3_requests(): void
    {
        $inquiry = $this->createConfirmedInquiry();
        $session = ['booking_access_tokens' => [$inquiry->id => $inquiry->token]];

        for ($i = 0; $i < 3; $i++) {
            $this->withSession($session)
                ->post(route('booking.portal.cancel', $inquiry))
                ->assertStatus(302);
        }

        $this->withSession($session)
            ->post(route('booking.portal.cancel', $inquiry))
            ->assertStatus(429);
    }

    public function test_review_rate_limited_after_3_requests(): void
    {
        $inquiry = $this->createConfirmedInquiry();
        $session = ['booking_access_tokens' => [$inquiry->id => $inquiry->token]];

        for ($i = 0; $i < 3; $i++) {
            $this->withSession($session)
                ->post(route('booking.portal.review', $inquiry), [
                    'rating' => 5,
                    'content' => "Review {$i}",
                ])->assertStatus(302);
        }

        $this->withSession($session)
            ->post(route('booking.portal.review', $inquiry), [
                'rating' => 5,
                'content' => 'Blocked review',
            ])->assertStatus(429);
    }

    public function test_modify_rate_limited_after_3_requests(): void
    {
        $inquiry = $this->createConfirmedInquiry();
        $session = ['booking_access_tokens' => [$inquiry->id => $inquiry->token]];
        $cottage = Cottage::first();

        for ($i = 0; $i < 3; $i++) {
            $day = 10 + $i;
            $this->withSession($session)
                ->put(route('booking.portal.modify.update', $inquiry), [
                    'booking_type' => 'overnight',
                    'cottage_id' => $cottage->id,
                    'check_in' => "2026-10-{$day}",
                    'check_out' => "2026-10-".($day + 1),
                    'pax' => 2,
                ])->assertStatus(302);
        }

        $this->withSession($session)
            ->put(route('booking.portal.modify.update', $inquiry), [
                'booking_type' => 'overnight',
                'cottage_id' => $cottage->id,
                'check_in' => '2026-10-20',
                'check_out' => '2026-10-21',
                'pax' => 2,
            ])->assertStatus(429);
    }

    public function test_payment_rate_limited_after_5_requests(): void
    {
        $inquiry = $this->createConfirmedInquiry();
        $session = ['booking_access_tokens' => [$inquiry->id => $inquiry->token]];

        for ($i = 0; $i < 5; $i++) {
            $this->withSession($session)
                ->post(route('payment.pay', $inquiry))
                ->assertStatus(302);
        }

        $this->withSession($session)
            ->post(route('payment.pay', $inquiry))
            ->assertStatus(429);
    }

    /*
    |--------------------------------------------------------------------------
    | Invoice PDF download — 10 req/min
    |--------------------------------------------------------------------------
    */

    public function test_invoice_pdf_rate_limited_after_10_requests(): void
    {
        $inquiry = $this->createConfirmedInquiry();
        $session = ['booking_access_tokens' => [$inquiry->id => $inquiry->token]];

        for ($i = 0; $i < 10; $i++) {
            $this->withSession($session)
                ->get(route('invoice.download', $inquiry))
                ->assertStatus(200);
        }

        $this->withSession($session)
            ->get(route('invoice.download', $inquiry))
            ->assertStatus(429);
    }

    /*
    |--------------------------------------------------------------------------
    | Admin — login lockout, password reset, exports
    |--------------------------------------------------------------------------
    */

    public function test_admin_login_rate_limited_after_5_requests(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/admin/login', [
                'email' => "admin{$i}@example.com",
                'password' => 'wrong-password',
            ])->assertStatus(302);
        }

        // Throttled: exception handler redirects back with a user-friendly
        // error flash instead of a raw 429.
        $this->post('/admin/login', [
            'email' => 'blocked-admin@example.com',
            'password' => 'wrong-password',
        ])->assertStatus(302)
            ->assertSessionHas('errors');
    }

    public function test_admin_export_rate_limited_after_5_requests(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($admin)
                ->get(route('admin.exports.inquiries'))
                ->assertStatus(200);
        }

        $this->actingAs($admin)
            ->get(route('admin.exports.inquiries'))
            ->assertStatus(429);
    }

    /*
    |--------------------------------------------------------------------------
    | Security headers
    |--------------------------------------------------------------------------
    */

    public function test_security_headers_present_on_public_page(): void
    {
        $response = $this->get('/');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');
    }

    public function test_hsts_header_only_sent_over_https_in_production(): void
    {
        // Non-production env: HSTS must never be emitted (local http dev).
        $response = $this->get('/');
        $response->assertHeaderMissing('Strict-Transport-Security');

        // Production + HTTPS (matching the real app.url host so CanonicalHost
        // passes through): HSTS IS emitted.
        $this->app->detectEnvironment(fn () => 'production');
        try {
            $response = $this->get('https://'.parse_url(config('app.url'), PHP_URL_HOST).'/');
            $response->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        } finally {
            $this->app->detectEnvironment(fn () => 'testing');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    private function createConfirmedInquiry(): Inquiry
    {
        $cottage = Cottage::first();

        return Inquiry::create([
            'reference_code' => Inquiry::generateReferenceCode(),
            'name' => 'Rate Limit Guest',
            'email' => 'ratelimit@example.com',
            'phone' => '09170000000',
            'booking_type' => 'overnight',
            'cottage_id' => $cottage->id,
            'check_in' => '2026-09-15',
            'check_out' => '2026-09-17',
            'pax' => 2,
            'status' => 'confirmed',
            'source' => 'website',
            'total_amount' => 5000.00,
        ]);
    }
}
