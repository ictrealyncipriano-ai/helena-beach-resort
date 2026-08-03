<?php

namespace Tests\Feature;

use App\Models\Cottage;
use App\Models\Inquiry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function confirmedBooking(string $email): Inquiry
    {
        $this->post('/book', [
            'name' => 'Guest',
            'email' => $email,
            'booking_type' => 'overnight',
            'cottage_id' => Cottage::first()->id,
            'check_in' => '2026-09-01',
            'check_out' => '2026-09-03',
            'pax' => 2,
        ]);

        $inquiry = Inquiry::where('email', $email)->first();

        $admin = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($admin)->post(route('admin.inquiries.confirm', $inquiry));

        return $inquiry->refresh();
    }

    public function test_pay_link_redirects_pending_booking_to_portal_with_error(): void
    {
        $this->post('/book', [
            'name' => 'Guest',
            'email' => 'pending@example.com',
            'booking_type' => 'day_tour',
            'cottage_id' => Cottage::first()->id,
            'check_in' => '2026-09-10',
            'pax' => 2,
        ]);

        $inquiry = Inquiry::where('email', 'pending@example.com')->first();

        $this->get(route('payment.pay', $inquiry))
            ->assertRedirect(route('booking.portal.show', $inquiry))
            ->assertSessionHas('error');
    }

    public function test_pay_creates_checkout_session_and_redirects(): void
    {
        $inquiry = $this->confirmedBooking('pay@example.com');

        Http::fake([
            'api.paymongo.com/v2/checkout_sessions' => Http::response([
                'data' => [
                    'id' => 'cs_test_123',
                    'attributes' => ['checkout_url' => 'https://checkout.paymongo.com/test123'],
                ],
            ], 200),
        ]);

        $response = $this->get(route('payment.pay', $inquiry));
        $response->assertRedirect('https://checkout.paymongo.com/test123');

        Http::assertSent(function ($request) use ($inquiry) {
            $body = $request->data();

            return $request->url() === 'https://api.paymongo.com/v2/checkout_sessions'
                && $request->hasHeader('Authorization', 'Basic '.base64_encode('sk_test_test-key:'))
                && $body['data']['attributes']['reference_number'] === $inquiry->reference_code
                && $body['data']['attributes']['line_items'][0]['amount'] === (int) round($inquiry->total_amount * 100)
                && $body['data']['attributes']['payment_method_types'] === ['gcash', 'paymaya', 'card'];
        });

        $this->assertDatabaseHas('inquiries', [
            'id' => $inquiry->id,
            'paymongo_session_id' => 'cs_test_123',
        ]);
    }

    public function test_pay_with_zero_amount_redirects_without_calling_api(): void
    {
        $inquiry = $this->confirmedBooking('zero@example.com');
        $inquiry->update(['total_amount' => 0]);

        Http::fake();

        $this->get(route('payment.pay', $inquiry))
            ->assertRedirect(route('booking.portal.show', $inquiry))
            ->assertSessionHas('error');

        Http::assertNothingSent();
    }

    public function test_pay_when_already_paid_redirects_without_calling_api(): void
    {
        $inquiry = $this->confirmedBooking('paid@example.com');
        $inquiry->update(['paid_at' => now(), 'paid_amount' => $inquiry->total_amount, 'payment_method' => 'gcash']);

        Http::fake();

        $this->get(route('payment.pay', $inquiry))
            ->assertRedirect(route('booking.portal.show', $inquiry))
            ->assertSessionHas('success');

        Http::assertNothingSent();
    }

    public function test_webhook_rejects_invalid_signature(): void
    {
        $this->postJson(route('payment.webhook'), ['data' => []], ['Paymongo-Signature' => 't=1,te=invalid'])
            ->assertStatus(401);
    }

    public function test_webhook_marks_confirmed_booking_as_paid(): void
    {
        $inquiry = $this->confirmedBooking('webhook@example.com');

        $payload = json_encode([
            'data' => [
                'type' => 'checkout_session.payment.paid',
                'data' => [
                    'id' => 'cs_abc',
                    'attributes' => [
                        'reference_number' => $inquiry->reference_code,
                        'payments' => [
                            [
                                'attributes' => [
                                    'amount' => 10000,
                                    'source' => ['type' => 'gcash'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $this->postJson(
            route('payment.webhook'),
            json_decode($payload, true),
            ['Paymongo-Signature' => $this->signatureFor($payload)]
        )->assertOk()->assertJson(['ok' => true]);

        $this->assertDatabaseHas('inquiries', [
            'id' => $inquiry->id,
            'payment_method' => 'gcash',
            'paid_amount' => 100.00,
            'paymongo_session_id' => 'cs_abc',
        ]);

        $this->assertNotNull($inquiry->refresh()->paid_at);
    }

    public function test_webhook_is_idempotent_for_repeat_payments(): void
    {
        $inquiry = $this->confirmedBooking('repeat@example.com');
        $inquiry->update(['paid_at' => now(), 'paid_amount' => 500, 'payment_method' => 'gcash']);

        $payload = json_encode([
            'data' => [
                'type' => 'checkout_session.payment.paid',
                'data' => [
                    'attributes' => [
                        'reference_number' => $inquiry->reference_code,
                        'payments' => [['attributes' => ['amount' => 50000, 'source' => ['type' => 'card']]]],
                    ],
                ],
            ],
        ]);

        $this->postJson(
            route('payment.webhook'),
            json_decode($payload, true),
            ['Paymongo-Signature' => $this->signatureFor($payload)]
        )->assertOk()->assertJson(['already_paid' => true]);

        $this->assertSame('gcash', $inquiry->refresh()->payment_method);
    }

    public function test_webhook_ignores_unrelated_events(): void
    {
        $inquiry = $this->confirmedBooking('ignore@example.com');

        $payload = json_encode([
            'data' => [
                'type' => 'checkout_session.created',
                'data' => ['attributes' => ['reference_number' => $inquiry->reference_code]],
            ],
        ]);

        $this->postJson(
            route('payment.webhook'),
            json_decode($payload, true),
            ['Paymongo-Signature' => $this->signatureFor($payload)]
        )->assertOk()->assertJson(['ignored' => true]);

        $this->assertNull($inquiry->refresh()->paid_at);
    }

    public function test_admin_can_mark_confirmed_booking_as_paid(): void
    {
        $inquiry = $this->confirmedBooking('manual@example.com');
        $admin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($admin)
            ->post(route('admin.inquiries.mark-paid', $inquiry))
            ->assertRedirect(route('admin.inquiries.show', $inquiry));

        $this->assertDatabaseHas('inquiries', [
            'id' => $inquiry->id,
            'payment_method' => 'manual',
            'paid_amount' => $inquiry->total_amount,
        ]);
        $this->assertNotNull($inquiry->refresh()->paid_at);
    }

    public function test_admin_cannot_mark_pending_booking_as_paid(): void
    {
        $this->post('/book', [
            'name' => 'Guest',
            'email' => 'nopay@example.com',
            'booking_type' => 'day_tour',
            'cottage_id' => Cottage::first()->id,
            'check_in' => '2026-09-10',
            'pax' => 2,
        ]);

        $inquiry = Inquiry::where('email', 'nopay@example.com')->first();
        $admin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($admin)
            ->post(route('admin.inquiries.mark-paid', $inquiry))
            ->assertSessionHas('error');

        $this->assertNull($inquiry->refresh()->paid_at);
    }

    private function signatureFor(string $payload): string
    {
        $timestamp = (string) time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$payload, 'test-webhook-secret');

        return "t={$timestamp},te={$signature},li=";
    }
}
