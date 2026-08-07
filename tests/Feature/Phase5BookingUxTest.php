<?php

namespace Tests\Feature;

use App\Mail\BookingExpired;
use App\Mail\BookingExpiringSoon;
use App\Models\Cottage;
use App\Models\CottageDateBlock;
use App\Models\Inquiry;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Phase 5 — Booking-Flow UX fixes.
 *
 * Covers: payment-return feedback (result banners + status endpoint),
 * unavailable-cottage CTA, double-submit idempotency, same-day check-out
 * rejection, Y-m-d blocked-date formatting, and phone-number fallbacks.
 */
class Phase5BookingUxTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        SiteSetting::forgetCache();
    }

    private function book(string $email, array $overrides = [])
    {
        return $this->post('/book', array_merge([
            'name' => 'Guest',
            'email' => $email,
            'booking_type' => 'overnight',
            'cottage_id' => Cottage::first()->id,
            'check_in' => '2026-09-01',
            'check_out' => '2026-09-03',
            'pax' => 2,
        ], $overrides));
    }

    private function portalSession(Inquiry $inquiry): array
    {
        return ['booking_access_tokens' => [$inquiry->id => $inquiry->token]];
    }

    /*
    |--------------------------------------------------------------------------
    | 5.1 Payment return feedback: success banner + status endpoint
    |--------------------------------------------------------------------------
    */

    public function test_booking_detail_renders_success_banner_when_result_is_success(): void
    {
        $this->book('result@example.com');
        $inquiry = Inquiry::where('email', 'result@example.com')->first();

        $this->withSession($this->portalSession($inquiry))
            ->get(route('booking.portal.show', ['inquiry' => $inquiry, 'result' => 'success']))
            ->assertOk()
            ->assertSee('Payment successful', false)
            ->assertSee('confirming your booking', false);
    }

    public function test_booking_detail_renders_cancelled_banner_when_result_is_cancelled(): void
    {
        $this->book('resultcancel@example.com');
        $inquiry = Inquiry::where('email', 'resultcancel@example.com')->first();

        $this->withSession($this->portalSession($inquiry))
            ->get(route('booking.portal.show', ['inquiry' => $inquiry, 'result' => 'cancelled']))
            ->assertOk()
            ->assertSee('Payment was cancelled', false)
            ->assertSee('no charge was made', false);
    }

    public function test_status_endpoint_reports_unpaid_for_pending_booking(): void
    {
        $this->book('statusunpaid@example.com');
        $inquiry = Inquiry::where('email', 'statusunpaid@example.com')->first();

        $this->withSession($this->portalSession($inquiry))
            ->getJson(route('booking.portal.status', $inquiry))
            ->assertOk()
            ->assertJson([
                'paid' => false,
                'status' => 'pending',
            ]);
    }

    public function test_status_endpoint_reports_paid_for_paid_booking(): void
    {
        $this->book('statuspaid@example.com');
        $inquiry = Inquiry::where('email', 'statuspaid@example.com')->first();
        $inquiry->update([
            'status' => 'confirmed',
            'paid_at' => now(),
            'paid_amount' => $inquiry->total_amount,
        ]);

        $this->withSession($this->portalSession($inquiry))
            ->getJson(route('booking.portal.status', $inquiry))
            ->assertOk()
            ->assertJson([
                'paid' => true,
                'status' => 'confirmed',
            ]);
    }

    public function test_status_endpoint_is_session_gated(): void
    {
        // Created directly (not via book()) so the session never holds the token.
        $cottage = Cottage::first();
        $inquiry = Inquiry::create([
            'reference_code' => 'HB-GATED1',
            'name' => 'Gated',
            'email' => 'statusgate@example.com',
            'check_in' => '2026-09-01',
            'check_out' => '2026-09-03',
            'cottage_id' => $cottage->id,
            'source' => 'website',
        ]);

        // No session token — the status must be as hidden as the rest of the portal.
        $this->getJson(route('booking.portal.status', $inquiry))->assertNotFound();
    }

    /*
    |--------------------------------------------------------------------------
    | 5.2 Unavailable-cottage CTA
    |--------------------------------------------------------------------------
    */

    public function test_unavailable_cottage_show_renders_disabled_cta_instead_of_book_link(): void
    {
        $cottage = Cottage::first();
        $cottage->update(['is_available' => false]);

        $this->get(route('cottages.show', $cottage))
            ->assertOk()
            ->assertSee('Currently Unavailable — Contact Us')
            ->assertDontSee('Book This Cottage');
    }

    public function test_unavailable_cottage_prefill_is_cleared_on_booking_form(): void
    {
        $unavailable = Cottage::first();
        $unavailable->update(['is_available' => false]);

        // Pick a different cottage that is still available.
        $available = Cottage::where('is_available', true)
            ->where('id', '!=', $unavailable->id)
            ->first();

        // Even though a cottage_id was requested, an unavailable cottage must
        // not be pre-selected in the form: the Alpine initial value stays empty.
        $this->get(route('book', ['cottage_id' => $unavailable->id]))
            ->assertOk()
            ->assertSee("cottageId: ''", false);

        // An available cottage is still pre-selected.
        $this->get(route('book', ['cottage_id' => $available->id]))
            ->assertOk()
            ->assertSee("cottageId: '{$available->id}'", false);
    }

    /*
    |--------------------------------------------------------------------------
    | 5.5 Double-submit idempotency
    |--------------------------------------------------------------------------
    */

    public function test_duplicate_submit_reuses_existing_pending_booking(): void
    {
        $this->book('duplicate@example.com');
        $first = Inquiry::where('email', 'duplicate@example.com')->first();

        $response = $this->book('duplicate@example.com');

        $this->assertSame(1, Inquiry::where('email', 'duplicate@example.com')->count());
        $response->assertRedirect(route('booking.confirmation', $first));
    }

    public function test_duplicate_submit_within_ten_minutes_is_reused(): void
    {
        $this->book('recent@example.com');

        // Simulate a retry 2 minutes later — still inside the idempotency window.
        $this->travel(2)->minutes();
        $first = Inquiry::where('email', 'recent@example.com')->first();

        $this->book('recent@example.com');

        $this->assertSame(1, Inquiry::where('email', 'recent@example.com')->count());
        $this->assertSame($first->id, Inquiry::where('email', 'recent@example.com')->first()->id);
    }

    public function test_same_guest_different_dates_is_not_treated_as_duplicate(): void
    {
        $this->book('different@example.com');

        $this->book('different@example.com', [
            'check_in' => '2026-10-01',
            'check_out' => '2026-10-03',
        ]);

        $this->assertSame(2, Inquiry::where('email', 'different@example.com')->count());
    }

    public function test_confirmed_booking_is_not_blocked_by_idempotency_guard(): void
    {
        $this->book('confirmeddup@example.com');
        $inquiry = Inquiry::where('email', 'confirmeddup@example.com')->first();
        $inquiry->update(['status' => 'confirmed']);

        // A different (later) stay for the same email must not be suppressed.
        $this->book('confirmeddup@example.com', [
            'check_in' => '2026-11-01',
            'check_out' => '2026-11-03',
        ]);

        $this->assertSame(2, Inquiry::where('email', 'confirmeddup@example.com')->count());
    }

    /*
    |--------------------------------------------------------------------------
    | 5.6 Same-day check-out rejection
    |--------------------------------------------------------------------------
    */

    public function test_same_day_check_out_is_rejected(): void
    {
        $this->book('sameday@example.com', [
            'check_in' => '2026-09-01',
            'check_out' => '2026-09-01',
        ])->assertSessionHasErrors(['check_out']);

        $this->assertDatabaseMissing('inquiries', ['email' => 'sameday@example.com']);
    }

    public function test_contact_flow_rejects_same_day_check_out(): void
    {
        $this->post('/contact', [
            'name' => 'Same Day',
            'email' => 'samedaycontact@example.com',
            'message' => 'Testing same-day check-out.',
            'check_in' => '2026-09-01',
            'check_out' => '2026-09-01',
        ])->assertSessionHasErrors(['check_out']);

        $this->assertDatabaseMissing('inquiries', ['email' => 'samedaycontact@example.com']);
    }

    /*
    |--------------------------------------------------------------------------
    | 5.9 Blocked dates are Y-m-d formatted
    |--------------------------------------------------------------------------
    */

    public function test_booking_form_blocked_dates_are_y_m_d(): void
    {
        $cottage = Cottage::first();
        $inquiry = Inquiry::create([
            'reference_code' => 'HB-BLOCK1',
            'name' => 'Blocker',
            'email' => 'blocker@example.com',
            'check_in' => '2026-09-10',
            'check_out' => '2026-09-12',
            'cottage_id' => $cottage->id,
            'source' => 'website',
        ]);
        $inquiry->reserveBlocks();

        $this->get(route('book'))
            ->assertOk()
            ->assertSee('2026-09-10')
            ->assertSee('2026-09-11')
            ->assertSee('2026-09-12');
    }

    public function test_cottage_show_blocked_dates_are_y_m_d(): void
    {
        $cottage = Cottage::first();
        $inquiry = Inquiry::create([
            'reference_code' => 'HB-BLOCK2',
            'name' => 'Blocker',
            'email' => 'blocker2@example.com',
            'check_in' => '2026-09-15',
            'check_out' => '2026-09-17',
            'cottage_id' => $cottage->id,
            'source' => 'website',
        ]);
        $inquiry->reserveBlocks();

        $this->get(route('cottages.show', $cottage))
            ->assertOk()
            ->assertSee('2026-09-15')
            ->assertSee('2026-09-16')
            ->assertSee('2026-09-17');
    }

    /*
    |--------------------------------------------------------------------------
    | 5.11 Phone number fallback
    |--------------------------------------------------------------------------
    */

    public function test_faq_renders_phone_when_configured(): void
    {
        SiteSetting::updateOrCreate(
            ['key' => 'contact_phone'],
            ['key' => 'contact_phone', 'value' => '0999 123 4567', 'type' => 'text']
        );
        SiteSetting::forgetCache();

        $this->get(route('faq'))
            ->assertOk()
            ->assertSee('tel:0999 123 4567')
            ->assertSee('Call us — 0999 123 4567')
            ->assertDontSee('Contact us for our number');
    }

    public function test_faq_renders_fallback_when_phone_unset(): void
    {
        SiteSetting::where('key', 'contact_phone')->delete();
        SiteSetting::forgetCache();

        $this->get(route('faq'))
            ->assertOk()
            ->assertSee('Contact us for our number')
            ->assertDontSee('tel:');
    }

    public function test_faq_renders_fallback_for_legacy_na_placeholder(): void
    {
        SiteSetting::updateOrCreate(
            ['key' => 'contact_phone'],
            ['key' => 'contact_phone', 'value' => 'N/A', 'type' => 'text']
        );
        SiteSetting::forgetCache();

        $this->get(route('faq'))
            ->assertOk()
            ->assertSee('Contact us for our number')
            ->assertDontSee('tel:');
    }

    /*
    |--------------------------------------------------------------------------
    | 5.4 Expiry warnings and notifications
    |--------------------------------------------------------------------------
    */

    public function test_command_warns_inquiries_expiring_soon_exactly_once(): void
    {
        Mail::fake();

        $cottage = Cottage::first();
        $inquiry = Inquiry::create([
            'name' => 'Warned Guest',
            'email' => 'warned@example.com',
            'check_in' => '2026-09-01',
            'check_out' => '2026-09-03',
            'cottage_id' => $cottage->id,
            'source' => 'website',
        ]);
        // Created ~30h ago: inside the warn window (24h–36h for a 48h hold).
        $inquiry->created_at = now()->subHours(30)->toDateTimeString();
        $inquiry->save();

        $this->artisan('reservations:release-expired --hours=48')->assertExitCode(0);

        Mail::assertSent(BookingExpiringSoon::class, fn ($mailable) => $mailable->hasTo('warned@example.com'));

        // Second run must not re-warn.
        $this->artisan('reservations:release-expired --hours=48')->assertExitCode(0);
        Mail::assertSent(BookingExpiringSoon::class, 1);

        $this->assertDatabaseHas('inquiries', [
            'id' => $inquiry->id,
            'status' => 'pending',
        ]);
        $this->assertNotNull($inquiry->refresh()->expiry_warned_at);
    }

    public function test_command_emails_guest_when_reservation_expires(): void
    {
        Mail::fake();

        $cottage = Cottage::first();
        $inquiry = Inquiry::create([
            'name' => 'Expired Guest',
            'email' => 'expired@example.com',
            'check_in' => '2026-09-01',
            'check_out' => '2026-09-03',
            'cottage_id' => $cottage->id,
            'source' => 'website',
        ]);
        $inquiry->created_at = now()->subHours(72)->toDateTimeString();
        $inquiry->save();
        $inquiry->reserveBlocks();

        $this->artisan('reservations:release-expired --hours=48')->assertExitCode(0);

        Mail::assertSent(BookingExpired::class, fn ($mailable) => $mailable->hasTo('expired@example.com'));
        $this->assertSame('expired', $inquiry->refresh()->status);
    }

    public function test_blocked_dates_are_released_when_reservation_expires(): void
    {
        Mail::fake();

        $cottage = Cottage::first();
        $inquiry = Inquiry::create([
            'name' => 'Release Guest',
            'email' => 'release@example.com',
            'check_in' => '2026-09-01',
            'check_out' => '2026-09-03',
            'cottage_id' => $cottage->id,
            'source' => 'website',
        ]);
        $inquiry->created_at = now()->subHours(72)->toDateTimeString();
        $inquiry->save();
        $inquiry->reserveBlocks();

        $this->artisan('reservations:release-expired --hours=48')->assertExitCode(0);

        $this->assertDatabaseMissing('cottage_date_blocks', [
            'cottage_id' => $cottage->id,
            'date' => '2026-09-01',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | 5.13 Cancellation & refund policy box on booking detail
    |--------------------------------------------------------------------------
    */

    public function test_booking_detail_renders_cancel_and_refund_policy(): void
    {
        $this->book('policy@example.com');
        $inquiry = Inquiry::where('email', 'policy@example.com')->first();

        $this->withSession($this->portalSession($inquiry))
            ->get(route('booking.portal.show', $inquiry))
            ->assertOk()
            ->assertSee('Cancellation &amp; refunds:', false)
            ->assertSee('24 hours before check-in', false)
            ->assertSee('refunded in full automatically', false);
    }
}
