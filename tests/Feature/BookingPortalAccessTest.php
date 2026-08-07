<?php

namespace Tests\Feature;

use App\Models\Cottage;
use App\Models\Inquiry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingPortalAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function createBooking(string $email = 'portal@example.com'): Inquiry
    {
        return Inquiry::create([
            'reference_code' => Inquiry::generateReferenceCode(),
            'name' => 'Portal Guest',
            'email' => $email,
            'phone' => '09170000000',
            'check_in' => '2026-10-01',
            'check_out' => '2026-10-03',
            'cottage_id' => Cottage::first()->id,
            'pax' => 2,
            'booking_type' => 'overnight',
            'status' => 'pending',
            'source' => 'website',
        ]);
    }

    public function test_guessing_inquiry_id_returns_404_everywhere(): void
    {
        $inquiry = $this->createBooking();

        $this->get(route('booking.portal.show', $inquiry))->assertStatus(404);
        $this->post(route('booking.portal.cancel', $inquiry))->assertStatus(404);
        $this->post(route('payment.pay', $inquiry))->assertStatus(404);
        $this->get(route('invoice.show', $inquiry))->assertStatus(404);
        $this->get(route('invoice.download', $inquiry))->assertStatus(404);
        $this->get(route('booking.confirmation', $inquiry))->assertStatus(404);
    }

    public function test_wrong_token_in_session_returns_404(): void
    {
        $inquiry = $this->createBooking();

        $this->withSession(['booking_access_tokens' => [$inquiry->id => str_repeat('x', 40)]])
            ->get(route('booking.portal.show', $inquiry))
            ->assertStatus(404);
    }

    public function test_matching_session_token_can_view_booking(): void
    {
        $inquiry = $this->createBooking();

        $this->withSession(['booking_access_tokens' => [$inquiry->id => $inquiry->token]])
            ->get(route('booking.portal.show', $inquiry))
            ->assertOk()
            ->assertSee($inquiry->reference_code);
    }

    public function test_lookup_grants_session_access(): void
    {
        $inquiry = $this->createBooking('lookup@example.com');

        $this->post(route('booking.portal.lookup.post'), [
            'email' => 'lookup@example.com',
            'reference_code' => $inquiry->reference_code,
        ])->assertRedirect(route('booking.portal.show', $inquiry));

        // The successful lookup stores the token in the session, so the
        // follow-up request (same browser session) can view the booking.
        $this->get(route('booking.portal.show', $inquiry))->assertOk();
    }

    public function test_lookup_with_wrong_reference_does_not_grant_access(): void
    {
        $inquiry = $this->createBooking('wrongref@example.com');

        $this->post(route('booking.portal.lookup.post'), [
            'email' => 'wrongref@example.com',
            'reference_code' => 'HB-WRONG1',
        ])->assertSessionHasErrors('reference_code');

        $this->get(route('booking.portal.show', $inquiry))->assertStatus(404);
    }

    public function test_cancel_requires_post(): void
    {
        $inquiry = $this->createBooking();

        $this->withSession(['booking_access_tokens' => [$inquiry->id => $inquiry->token]])
            ->get(route('booking.portal.cancel', $inquiry))
            ->assertStatus(405);
    }

    public function test_cancel_with_matching_session_cancels(): void
    {
        $inquiry = $this->createBooking();

        $this->withSession(['booking_access_tokens' => [$inquiry->id => $inquiry->token]])
            ->post(route('booking.portal.cancel', $inquiry))
            ->assertRedirect(route('booking.portal.show', $inquiry));

        $this->assertSame('cancelled', $inquiry->refresh()->status);
    }
}
