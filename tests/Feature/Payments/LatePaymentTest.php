<?php

namespace Tests\Feature\Payments;

use App\Mail\InquiryNotification;
use App\Models\Cottage;
use App\Models\Inquiry;
use App\Models\Payment;
use App\Models\User;
use App\Services\PaymentReconciliationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * WP-6: late payments (money arriving after cancel/expire) are preserved in
 * the ledger and automatically refunded — never ignored, never recorded
 * against the dead booking's summary.
 */
class LatePaymentTest extends TestCase
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

    private function paidPayload(Inquiry $inquiry, string $paymentId, int $centavos): string
    {
        return json_encode([
            'data' => [
                'id' => 'cs_late_'.$paymentId,
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

    private function cancelUnpaid(Inquiry $inquiry): Inquiry
    {
        $this->withSession($this->portalSession($inquiry))
            ->post(route('booking.portal.cancel', $inquiry));

        return tap($inquiry->refresh(), fn ($i) => $this->assertSame('cancelled', $i->status));
    }

    public function test_late_payment_on_cancelled_booking_is_refunded_not_recorded(): void
    {
        Mail::fake();
        $inquiry = $this->cancelUnpaid($this->confirmedBooking('lateref@example.com'));

        Http::fake([
            'api.paymongo.com/v1/refunds' => Http::response(['data' => ['id' => 'rfnd_late_1']], 200),
        ]);

        $centavos = (int) round((float) $inquiry->total_amount * 100);
        $payload = $this->paidPayload($inquiry, 'pay_late_1', $centavos);

        $this->postJson(
            route('payment.webhook'),
            json_decode($payload, true),
            ['Paymongo-Signature' => $this->signatureFor($payload)]
        )->assertOk()->assertJson(['late_payment' => true, 'refund' => 'refunded']);

        Http::assertSent(function ($request) use ($centavos) {
            $body = $request->data();

            return $request->url() === 'https://api.paymongo.com/v1/refunds'
                && $body['data']['attributes']['payment_id'] === 'pay_late_1'
                && $body['data']['attributes']['amount'] === $centavos;
        });

        // Ledger preserved both sides of the money movement...
        $late = Payment::where('provider_payment_id', 'pay_late_1')->firstOrFail();
        $this->assertSame(Payment::STATUS_REFUNDED, $late->status);
        $this->assertSame($inquiry->id, $late->inquiry_id);
        $refund = Payment::where('inquiry_id', $inquiry->id)
            ->where('type', Payment::TYPE_REFUND)->firstOrFail();
        $this->assertSame('rfnd_late_1', $refund->provider_refund_id);

        // ...while the dead booking's summary is untouched.
        $inquiry->refresh();
        $this->assertSame('cancelled', $inquiry->status);
        $this->assertSame('0.00', (string) ($inquiry->amount_paid ?? '0.00'));
        $this->assertNull($inquiry->paymongo_payment_id);
        $this->assertNull($inquiry->refunded_at);

        Mail::assertQueued(InquiryNotification::class);
    }

    public function test_late_payment_on_expired_booking_is_refunded(): void
    {
        Mail::fake();
        $inquiry = $this->confirmedBooking('lateexp@example.com');
        $inquiry->update(['status' => Inquiry::STATUS_EXPIRED]);

        Http::fake([
            'api.paymongo.com/v1/refunds' => Http::response(['data' => ['id' => 'rfnd_late_exp']], 200),
        ]);

        $payload = $this->paidPayload($inquiry, 'pay_late_exp', (int) round((float) $inquiry->total_amount * 100));

        $this->postJson(
            route('payment.webhook'),
            json_decode($payload, true),
            ['Paymongo-Signature' => $this->signatureFor($payload)]
        )->assertOk()->assertJson(['late_payment' => true, 'refund' => 'refunded']);

        $this->assertSame('expired', $inquiry->refresh()->status);
        $this->assertSame(
            (float) $inquiry->total_amount,
            (float) Payment::where('provider_payment_id', 'pay_late_exp')->firstOrFail()->amount
        );
    }

    public function test_failed_late_refund_leaves_retryable_state_without_touching_summary(): void
    {
        $inquiry = $this->cancelUnpaid($this->confirmedBooking('latefail@example.com'));

        Http::fake([
            'api.paymongo.com/v1/refunds' => Http::response(['errors' => ['boom']], 500),
        ]);

        $payload = $this->paidPayload($inquiry, 'pay_late_fail', (int) round((float) $inquiry->total_amount * 100));

        $this->postJson(
            route('payment.webhook'),
            json_decode($payload, true),
            ['Paymongo-Signature' => $this->signatureFor($payload)]
        )->assertOk()->assertJson(['late_payment' => true, 'refund' => 'failed']);

        // Money preserved as requires_refund for WP-7 retry; summary clean.
        $late = Payment::where('provider_payment_id', 'pay_late_fail')->firstOrFail();
        $this->assertSame(Payment::STATUS_REQUIRES_REFUND, $late->status);

        $inquiry->refresh();
        $this->assertSame('0.00', (string) ($inquiry->amount_paid ?? '0.00'));
        $this->assertNull($inquiry->refunded_at);
    }

    public function test_duplicate_late_delivery_refunds_only_once(): void
    {
        $inquiry = $this->cancelUnpaid($this->confirmedBooking('latedup@example.com'));

        Http::fake([
            'api.paymongo.com/v1/refunds' => Http::response(['data' => ['id' => 'rfnd_late_dup']], 200),
        ]);

        $payload = $this->paidPayload($inquiry, 'pay_late_dup', (int) round((float) $inquiry->total_amount * 100));

        $deliver = fn () => $this->postJson(
            route('payment.webhook'),
            json_decode($payload, true),
            ['Paymongo-Signature' => $this->signatureFor($payload)]
        )->assertOk();

        $deliver()->assertJson(['late_payment' => true]);
        // Re-sign: signatures are time-bound.
        $payload2 = $this->paidPayload($inquiry->refresh(), 'pay_late_dup', (int) round((float) $inquiry->refresh()->total_amount * 100));
        $this->postJson(
            route('payment.webhook'),
            json_decode($payload2, true),
            ['Paymongo-Signature' => $this->signatureFor($payload2)]
        )->assertOk()->assertJson(['duplicate_payment' => true]);

        $this->assertSame(1, Payment::where('provider_payment_id', 'pay_late_dup')->count());

        $refundCalls = collect(Http::recorded())
            ->filter(fn ($pair) => str_contains($pair[0]->url(), '/v1/refunds'))
            ->count();
        $this->assertSame(1, $refundCalls);
    }

    public function test_payment_on_pending_booking_is_still_ignored(): void
    {
        // Pending bookings may still be confirmed — refunding now would be
        // wrong, so the historical ignore + owner-alert stands.
        $this->post('/book', [
            'name' => 'Guest',
            'email' => 'latepending@example.com',
            'booking_type' => 'day_tour',
            'cottage_id' => Cottage::first()->id,
            'check_in' => '2026-09-10',
            'pax' => 2,
        ]);

        $inquiry = Inquiry::where('email', 'latepending@example.com')->firstOrFail();
        $this->assertSame('pending', $inquiry->status);

        Http::fake();

        $payload = $this->paidPayload($inquiry, 'pay_late_pend', (int) round((float) $inquiry->total_amount * 100));

        $this->postJson(
            route('payment.webhook'),
            json_decode($payload, true),
            ['Paymongo-Signature' => $this->signatureFor($payload)]
        )->assertOk()->assertJson(['ignored' => true]);

        Http::assertNothingSent();
        $this->assertSame(0, Payment::where('inquiry_id', $inquiry->id)->count());
    }

    public function test_reconcile_by_checkout_recovers_late_payment(): void
    {
        $inquiry = $this->cancelUnpaid($this->confirmedBooking('laterecon@example.com'));
        $inquiry->update(['paymongo_session_id' => 'cs_late_recon']);

        Http::fake([
            'api.paymongo.com/v2/checkout_sessions/*' => Http::response([
                'data' => [
                    'id' => 'cs_late_recon',
                    'type' => 'checkout_session',
                    'attributes' => [
                        'reference_number' => $inquiry->refresh()->reference_code,
                        'paid_at' => 1785892089,
                        'payments' => [
                            [
                                'id' => 'pay_late_recon',
                                'attributes' => [
                                    'status' => 'paid',
                                    'amount' => (int) round((float) $inquiry->refresh()->total_amount * 100),
                                    'currency' => 'PHP',
                                    'source' => ['type' => 'qrph'],
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
            'api.paymongo.com/v1/refunds' => Http::response(['data' => ['id' => 'rfnd_late_recon']], 200),
        ]);

        $result = app(PaymentReconciliationService::class)->reconcileByCheckoutId('cs_late_recon');

        $this->assertSame('late_payment', $result['outcome']);
        $this->assertSame('refunded', $result['refund']);
        $this->assertNotNull(Payment::where('provider_payment_id', 'pay_late_recon')->first());
    }
}
