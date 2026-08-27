<?php

namespace Tests\Feature;

use App\Mail\ManualRefundRequired;
use App\Models\Cottage;
use App\Models\Inquiry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class DepositPaymentFlowTest extends TestCase
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

    /**
     * Simulate the state pay() leaves behind for a deposit checkout: the
     * pending amount (what this session is collecting) plus a deposit split.
     */
    private function withDepositPending(Inquiry $inquiry, string $deposit): Inquiry
    {
        $inquiry->update([
            'deposit_amount' => $deposit,
            'payment_pending_amount' => $deposit,
        ]);

        return $inquiry->refresh();
    }

    private function depositWebhookPayload(Inquiry $inquiry, string $paymentId = 'pay_dep'): array
    {
        return [
            'data' => [
                'id' => 'evt_dep',
                'type' => 'checkout_session.payment.paid',
                'attributes' => [
                    'data' => [
                        'id' => 'cs_dep',
                        'type' => 'checkout_session',
                        'attributes' => [
                            'reference_number' => $inquiry->reference_code,
                            'paid_at' => 1785892089,
                            'payments' => [
                                [
                                    'id' => $paymentId,
                                    'attributes' => [
                                        'status' => 'paid',
                                        'amount' => (int) round($inquiry->payment_pending_amount * 100),
                                        'source' => ['type' => 'qrph'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'previous_data' => null,
                ],
            ],
        ];
    }

    private function portalSession(Inquiry $inquiry): array
    {
        return ['booking_access_tokens' => [$inquiry->id => $inquiry->token]];
    }

    private function signatureFor(array $payload): string
    {
        $json = json_encode($payload);
        $timestamp = (string) time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$json, 'test-webhook-secret');

        return "t={$timestamp},te={$signature},li=";
    }

    public function test_webhook_records_deposit_without_marking_booking_fully_paid(): void
    {
        $inquiry = $this->withDepositPending($this->confirmedBooking('dep@example.com'), '1500.00');

        $payload = $this->depositWebhookPayload($inquiry);

        $this->postJson(route('payment.webhook'), $payload, ['Paymongo-Signature' => $this->signatureFor($payload)])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $inquiry->refresh();
        $this->assertSame('1500.00', (string) $inquiry->amount_paid);
        $this->assertNotNull($inquiry->deposit_paid_at);
        $this->assertNull($inquiry->fully_paid_at);
        $this->assertNull($inquiry->refresh()->fully_paid_at);
        $this->assertFalse($inquiry->isPaid());
        $this->assertNull($inquiry->payment_pending_amount);
        $this->assertSame('pay_dep', $inquiry->paymongo_payment_id);
    }

    public function test_webhook_duplicate_delivery_credits_deposit_only_once(): void
    {
        $inquiry = $this->withDepositPending($this->confirmedBooking('dup@example.com'), '1500.00');

        $payload = $this->depositWebhookPayload($inquiry);

        $headers = ['Paymongo-Signature' => $this->signatureFor($payload)];

        $this->postJson(route('payment.webhook'), $payload, $headers)->assertOk();
        $this->postJson(route('payment.webhook'), $payload, $headers)
            ->assertOk()
            ->assertJson(['duplicate_payment' => true]);

        // The re-delivery must NOT have added another ₱1,500.
        $this->assertSame('1500.00', (string) $inquiry->refresh()->amount_paid);
    }

    public function test_webhook_resolves_inquiry_via_external_reference_number(): void
    {
        $inquiry = $this->confirmedBooking('extref@example.com');

        $total = (float) $inquiry->total_amount;
        $inquiry->update(['payment_pending_amount' => $inquiry->total_amount]);
        $inquiry->refresh();

        $payload = json_encode([
            'data' => [
                'id' => 'evt_ext',
                'type' => 'checkout_session.payment.paid',
                'attributes' => [
                    'data' => [
                        'id' => 'cs_ext',
                        'type' => 'checkout_session',
                        'attributes' => [
                            'external_reference_number' => $inquiry->reference_code,
                            'paid_at' => 1785892089,
                            'payments' => [
                                ['id' => 'pay_ext', 'attributes' => ['status' => 'paid', 'amount' => (int) round($total * 100), 'source' => ['type' => 'qrph']]],
                            ],
                        ],
                    ],
                    'previous_data' => null,
                ],
            ],
        ]);

        $this->postJson(
            route('payment.webhook'),
            json_decode($payload, true),
            ['Paymongo-Signature' => $this->signatureFor(json_decode($payload, true))]
        )->assertOk();

        $this->assertTrue($inquiry->refresh()->isPaid());
    }

    public function test_failed_checkout_creation_leaves_no_stale_pending_amount(): void
    {
        $inquiry = $this->confirmedBooking('stale@example.com');

        Http::fake([
            'api.paymongo.com/v2/checkout_sessions' => Http::response(['errors' => ['boom']], 500),
        ]);

        $this->withSession($this->portalSession($inquiry))
            ->post(route('payment.pay', $inquiry))
            ->assertRedirect(route('booking.portal.show', $inquiry))
            ->assertSessionHas('error');

        // A failed session must not leave an expected amount behind: that
        // stale value would become the next webhook's verification baseline.
        $inquiry->refresh();
        $this->assertNull($inquiry->payment_pending_amount);
        $this->assertNull($inquiry->paymongo_session_id);
    }

    public function test_guest_cancel_of_deposit_only_booking_refunds_exactly_the_deposit(): void
    {
        Mail::fake();
        $inquiry = $this->withDepositPending($this->confirmedBooking('depcancel@example.com'), '1500.00');

        // Record the deposit payment exactly like the webhook does.
        $inquiry->update([
            'amount_paid' => '1500.00',
            'deposit_paid_at' => now(),
            'payment_pending_amount' => null,
            'payment_method' => 'qrph',
            'paymongo_payment_id' => 'pay_dep',
            'paymongo_session_id' => 'cs_dep',
        ]);

        Http::fake([
            'api.paymongo.com/v1/refunds' => Http::response(['data' => ['id' => 'rfnd_1']], 200),
        ]);

        $this->withSession($this->portalSession($inquiry))
            ->post(route('booking.portal.cancel', $inquiry))
            ->assertRedirect(route('booking.portal.show', $inquiry));

        Http::assertSent(function ($request) {
            $body = $request->data();

            return $request->url() === 'https://api.paymongo.com/v1/refunds'
                && $body['data']['attributes']['payment_id'] === 'pay_dep'
                && $body['data']['attributes']['amount'] === 150000;
        });

        $inquiry->refresh();
        $this->assertSame('cancelled', $inquiry->status);
        $this->assertSame('1500.00', (string) $inquiry->refund_amount);
        $this->assertNotNull($inquiry->refunded_at);
    }

    public function test_guest_cancel_of_manually_paid_booking_flags_owner_instead_of_refunding(): void
    {
        Mail::fake();
        $inquiry = $this->confirmedBooking('manualcancel@example.com');

        // Money collected by hand: no PayMongo payment id to refund against.
        $inquiry->recordManualPayment((string) $inquiry->total_amount);

        Http::fake();

        $this->withSession($this->portalSession($inquiry))
            ->post(route('booking.portal.cancel', $inquiry))
            ->assertRedirect(route('booking.portal.show', $inquiry))
            ->assertSessionHas('warning');

        // No online refund attempt — the money must be returned offline.
        Http::assertNothingSent();

        $inquiry->refresh();
        $this->assertSame('cancelled', $inquiry->status);
        $this->assertNull($inquiry->refunded_at);
        $this->assertNull($inquiry->refund_amount);

        Mail::assertSent(ManualRefundRequired::class);
    }
}
