<?php

namespace Tests\Feature\Payments;

use App\Models\Cottage;
use App\Models\Inquiry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * WP-0 characterization: locks CURRENT payment intent before Phase 5 hardening.
 *
 * No behavior change here. These tests document the baseline that
 * WP-3 (checkout expiry) and WP-6 (late-payment) will intentionally change:
 *
 * - abandoned checkout currently leaves payment_pending_amount behind
 * - refund failure still cancels but leaves refunded_at null
 * - duplicate webhook never double-credits
 * - amount mismatch never records
 *
 * NOTE (WP-6): late payments on cancelled/expired bookings were ignored here;
 * they now enter the ledger + auto-refund workflow (see LatePaymentTest).
 */
class PaymentCharacterizationTest extends TestCase
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

        $inquiry = Inquiry::where('email', $email)->firstOrFail();

        $admin = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($admin)->post(route('admin.inquiries.confirm', $inquiry));

        return $inquiry->refresh();
    }

    private function portalSession(Inquiry $inquiry): array
    {
        return ['booking_access_tokens' => [$inquiry->id => $inquiry->token]];
    }

    private function signatureFor(string $payload): string
    {
        $timestamp = (string) time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$payload, 'test-webhook-secret');

        return "t={$timestamp},te={$signature},li=";
    }

    private function paidWebhookPayload(Inquiry $inquiry, string $paymentId, int $centavos): string
    {
        return json_encode([
            'data' => [
                'id' => 'cs_char_'.$paymentId,
                'type' => 'checkout_session',
                'attributes' => [
                    'reference_number' => $inquiry->reference_code,
                    'payments' => [
                        [
                            'id' => $paymentId,
                            'attributes' => [
                                'status' => 'paid',
                                'amount' => $centavos,
                                'currency' => 'PHP',
                                'source' => ['type' => 'qrph'],
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function test_abandoned_checkout_leaves_pending_amount_behind(): void
    {
        // Baseline WP-0: creating a checkout sets pending; nothing ages it out.
        // WP-3 will change this by clearing stale pendings after TTL.
        $inquiry = $this->confirmedBooking('abandoned@example.com');

        Http::fake([
            'api.paymongo.com/v2/checkout_sessions' => Http::response([
                'data' => [
                    'id' => 'cs_abandoned',
                    'attributes' => ['checkout_url' => 'https://checkout.paymongo.com/abandoned'],
                ],
            ], 200),
        ]);

        $this->withSession($this->portalSession($inquiry))
            ->post(route('payment.pay', $inquiry))
            ->assertRedirect('https://checkout.paymongo.com/abandoned');

        $inquiry->refresh();
        $this->assertNotNull($inquiry->payment_pending_amount);
        $this->assertSame('cs_abandoned', $inquiry->paymongo_session_id);
        // Guest never pays; pending stays (current behavior, WP-3 fixes).
        $this->assertNull($inquiry->fully_paid_at);
    }

    public function test_refund_failure_still_cancels_without_marking_refunded(): void
    {
        $inquiry = $this->confirmedBooking('charrefundfail@example.com');
        $inquiry->update([
            'amount_paid' => $inquiry->total_amount,
            'fully_paid_at' => now(),
            'payment_method' => 'qrph',
            'paymongo_payment_id' => 'pay_char_fail',
        ]);

        Http::fake([
            'api.paymongo.com/v1/refunds' => Http::response(['errors' => ['boom']], 500),
        ]);

        $this->withSession($this->portalSession($inquiry))
            ->post(route('booking.portal.cancel', $inquiry))
            ->assertRedirect(route('booking.portal.show', $inquiry));

        $inquiry->refresh();
        $this->assertSame('cancelled', $inquiry->status);
        // Failed refund must never look successful.
        $this->assertNull($inquiry->refunded_at);
    }

    public function test_duplicate_webhook_never_double_credits(): void
    {
        $inquiry = $this->confirmedBooking('chardup@example.com');
        $inquiry->update(['payment_pending_amount' => $inquiry->total_amount]);
        $inquiry->refresh();

        $payload = $this->paidWebhookPayload(
            $inquiry,
            'pay_char_dup',
            (int) round((float) $inquiry->total_amount * 100)
        );
        $headers = ['Paymongo-Signature' => $this->signatureFor($payload)];

        // First delivery records; signature is time-bound so re-sign second.
        $this->postJson(route('payment.webhook'), json_decode($payload, true), $headers)
            ->assertOk()->assertJson(['ok' => true]);

        $firstPaid = (string) $inquiry->refresh()->amount_paid;

        $payload2 = $this->paidWebhookPayload(
            $inquiry->refresh(),
            'pay_char_dup',
            (int) round((float) $inquiry->refresh()->total_amount * 100)
        );
        $this->postJson(
            route('payment.webhook'),
            json_decode($payload2, true),
            ['Paymongo-Signature' => $this->signatureFor($payload2)]
        )->assertOk();

        $this->assertSame($firstPaid, (string) $inquiry->refresh()->amount_paid);
    }

    public function test_amount_mismatch_never_records_payment(): void
    {
        $inquiry = $this->confirmedBooking('charmismatch@example.com');
        $inquiry->update(['payment_pending_amount' => $inquiry->total_amount]);

        $payload = $this->paidWebhookPayload($inquiry, 'pay_char_mm', 1);

        $this->postJson(
            route('payment.webhook'),
            json_decode($payload, true),
            ['Paymongo-Signature' => $this->signatureFor($payload)]
        )->assertStatus(400);

        $inquiry->refresh();
        $this->assertNull($inquiry->fully_paid_at);
        $this->assertNull($inquiry->paymongo_payment_id);
    }
}
