<?php

namespace Tests\Feature;

use App\Models\Cottage;
use App\Models\Inquiry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Slice 4 — high-impact UX dead ends (characterization-first).
 *
 * Each test asserts the DESIRED end state and must FAIL pre-fix:
 *  1. Expired lookup-session error is rendered on the lookup page.
 *  2. The post-payment poller has a terminal timeout/reassurance notice.
 *  3. Pending unpaid bookings explain the Pay next steps.
 *  4. The cottages availability widget offers a booking fallback on error.
 */
class BookingDeadEndsCharacterizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function booking(array $overrides = []): Inquiry
    {
        $inquiry = Inquiry::create(array_merge([
            'reference_code' => Inquiry::generateReferenceCode(),
            'name' => 'Dead End Guest',
            'email' => 'deadend@example.com',
            'phone' => '09170000000',
            'check_in' => '2026-10-01',
            'check_out' => '2026-10-03',
            'cottage_id' => Cottage::first()->id,
            'pax' => 2,
            'booking_type' => 'overnight',
            'status' => 'confirmed',
            'total_amount' => '4000.00',
            'source' => 'booking',
        ], $overrides));

        $this->withSession(['booking_access_tokens' => [$inquiry->id => $inquiry->token]]);

        return $inquiry;
    }

    public function test_lookup_renders_expired_session_error(): void
    {
        $message = 'Your booking session has expired. Please look up your booking again to continue.';

        $this->withSession(['error' => $message])
            ->get(route('booking.portal.lookup'))
            ->assertOk()
            ->assertSee($message);
    }

    public function test_payment_poller_has_terminal_timeout_notice(): void
    {
        $inquiry = $this->booking(['email' => 'poller@example.com']);

        $this->get(route('booking.portal.show', [$inquiry, 'result' => 'success']))
            ->assertOk()
            ->assertSee('Still confirming your payment', false);
    }

    public function test_pending_unpaid_booking_explains_pay_next_steps(): void
    {
        $inquiry = $this->booking([
            'email' => 'pendingpay@example.com',
            'status' => 'pending',
        ]);

        $this->get(route('booking.portal.show', $inquiry))
            ->assertOk()
            ->assertSee('then the Pay button appears here', false);
    }

    public function test_availability_error_offers_booking_fallback(): void
    {
        $this->get(route('cottages.index'))
            ->assertOk()
            ->assertSee('Continue to booking anyway', false);
    }
}
