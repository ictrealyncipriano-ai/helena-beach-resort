<?php

namespace Tests\Feature;

use App\Models\Cottage;
use App\Models\Inquiry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function portalSession(Inquiry $inquiry): array
    {
        return ['booking_access_tokens' => [$inquiry->id => $inquiry->token]];
    }

    /*
    |--------------------------------------------------------------------------
    | Admin modal — Escape key & ARIA
    |--------------------------------------------------------------------------
    */

    public function test_admin_modal_contains_escape_key_handler(): void
    {
        $user = User::first();
        $this->actingAs($user);

        $response = $this->get('/admin/cottages');
        $response->assertStatus(200);
        $response->assertSee('keydown.escape.window', false);
    }

    public function test_admin_modal_has_aria_dialog_attributes(): void
    {
        $user = User::first();
        $this->actingAs($user);

        $response = $this->get('/admin/cottages');
        $response->assertStatus(200);
        $response->assertSee('role="dialog"', false);
        $response->assertSee('aria-modal="true"', false);
    }

    public function test_admin_modal_has_focus_return_mechanism(): void
    {
        $user = User::first();
        $this->actingAs($user);

        $response = $this->get('/admin/cottages');
        $response->assertStatus(200);
        $response->assertSee('_previousFocus', false);
    }

    /*
    |--------------------------------------------------------------------------
    | Confirm dialog — Escape key & ARIA
    |--------------------------------------------------------------------------
    */

    public function test_confirm_dialog_contains_escape_key_handler(): void
    {
        $user = User::first();
        $this->actingAs($user);

        $response = $this->get('/admin/cottages');
        $response->assertStatus(200);
        $response->assertSee('keydown.escape.window', false);
    }

    public function test_confirm_dialog_has_aria_dialog_attributes(): void
    {
        $user = User::first();
        $this->actingAs($user);

        $response = $this->get('/admin/cottages');
        $response->assertStatus(200);
        $response->assertSee('role="dialog"', false);
        $response->assertSee('aria-modal="true"', false);
    }

    /*
    |--------------------------------------------------------------------------
    | Star rating — role="radio"
    |--------------------------------------------------------------------------
    */

    public function test_star_rating_buttons_have_role_radio(): void
    {
        $cottage = Cottage::first();
        $inquiry = Inquiry::create([
            'reference_code' => Inquiry::generateReferenceCode(),
            'name' => 'A11y Guest',
            'email' => 'a11y@example.com',
            'booking_type' => 'overnight',
            'cottage_id' => $cottage->id,
            'check_in' => '2026-08-10',
            'check_out' => '2026-08-12',
            'pax' => 2,
            'status' => 'confirmed',
            'source' => 'website',
            'total_amount' => 5000.00,
            'amount_paid' => 5000.00,
        ]);

        $response = $this->withSession($this->portalSession($inquiry))
            ->get(route('booking.portal.show', $inquiry));
        $response->assertOk();
        $response->assertSee('role="radiogroup"', false);
        $response->assertSee('role="radio"', false);
        $response->assertSee('aria-label="Rating"', false);
    }

    /*
    |--------------------------------------------------------------------------
    | Booking detail cancel modal — focus return
    |--------------------------------------------------------------------------
    */

    public function test_cancel_modal_has_escape_handler_and_focus_return(): void
    {
        $cottage = Cottage::first();
        $inquiry = Inquiry::create([
            'reference_code' => Inquiry::generateReferenceCode(),
            'name' => 'Focus Guest',
            'email' => 'focus@example.com',
            'booking_type' => 'overnight',
            'cottage_id' => $cottage->id,
            'check_in' => '2026-09-20',
            'check_out' => '2026-09-22',
            'pax' => 2,
            'status' => 'confirmed',
            'source' => 'website',
            'total_amount' => 5000.00,
        ]);

        $response = $this->withSession($this->portalSession($inquiry))
            ->get(route('booking.portal.show', $inquiry));
        $response->assertOk();
        $response->assertSee('showCancelModal', false);
        $response->assertSee('_cancelPreviousFocus', false);
        $response->assertSee('role="dialog"', false);
        $response->assertSee('aria-modal="true"', false);
        $response->assertSee('aria-labelledby="cancel-booking-title"', false);
    }

    /*
    |--------------------------------------------------------------------------
    | prefers-reduced-motion — CSS presence
    |--------------------------------------------------------------------------
    */

    public function test_reduced_motion_media_query_exists_in_css(): void
    {
        $cssPath = resource_path('css/app.css');
        $css = file_get_contents($cssPath);
        $this->assertStringContainsString('prefers-reduced-motion', $css);
        $this->assertStringContainsString('animation-duration: 0.01ms', $css);
    }

    /*
    |--------------------------------------------------------------------------
    | Security headers — regression guard
    |--------------------------------------------------------------------------
    */

    public function test_security_headers_present(): void
    {
        $response = $this->get('/');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');
    }
}
