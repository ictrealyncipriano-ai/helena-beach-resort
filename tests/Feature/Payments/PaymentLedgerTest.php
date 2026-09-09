<?php

namespace Tests\Feature\Payments;

use App\Models\Cottage;
use App\Models\Inquiry;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * WP-1: additive ledger + dual-write. The inquiries.* summary columns
 * remain the source of truth; every test asserts summary AND ledger agree.
 */
class PaymentLedgerTest extends TestCase
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

    private function paidPayload(Inquiry $inquiry, string $paymentId, int $centavos): array
    {
        return [
            'data' => [
                'id' => 'cs_'.$paymentId,
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
        ];
    }

    public function test_webhook_creates_ledger_row_matching_summary(): void
    {
        $inquiry = $this->confirmedBooking('ledger@example.com');

        $payload = $this->paidPayload($inquiry, 'pay_ledger_1', (int) round((float) $inquiry->total_amount * 100));
        $json = json_encode($payload);

        $this->postJson(route('payment.webhook'), $payload, ['Paymongo-Signature' => $this->signatureFor($json)])
            ->assertOk()->assertJson(['ok' => true]);

        $inquiry->refresh();
        $ledger = Payment::where('inquiry_id', $inquiry->id)->where('type', '!=', Payment::TYPE_REFUND)->firstOrFail();

        $this->assertSame('pay_ledger_1', $ledger->provider_payment_id);
        $this->assertSame(Payment::STATUS_PAID, $ledger->status);
        $this->assertSame((float) $inquiry->amount_paid, (float) $ledger->amount);
        $this->assertSame('qrph', $ledger->method);
    }

    public function test_duplicate_webhook_creates_only_one_ledger_row(): void
    {
        $inquiry = $this->confirmedBooking('ledgerdup@example.com');

        $payload = $this->paidPayload($inquiry, 'pay_ledger_dup', (int) round((float) $inquiry->total_amount * 100));

        $json = json_encode($payload);
        $this->postJson(route('payment.webhook'), $payload, ['Paymongo-Signature' => $this->signatureFor($json)])->assertOk();

        $payload2 = $this->paidPayload($inquiry->refresh(), 'pay_ledger_dup', (int) round((float) $inquiry->refresh()->total_amount * 100));
        $json2 = json_encode($payload2);
        $this->postJson(route('payment.webhook'), $payload2, ['Paymongo-Signature' => $this->signatureFor($json2)])->assertOk();

        $this->assertSame(1, Payment::where('inquiry_id', $inquiry->id)->count());
    }

    public function test_manual_payment_creates_ledger_row(): void
    {
        $inquiry = $this->confirmedBooking('ledgermanual@example.com');
        $admin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($admin)->post(route('admin.inquiries.mark-paid', $inquiry));

        $inquiry->refresh();
        $ledger = Payment::where('inquiry_id', $inquiry->id)->firstOrFail();

        $this->assertSame(Payment::PROVIDER_MANUAL, $ledger->provider);
        $this->assertSame((float) $inquiry->amount_paid, (float) $ledger->amount);
    }

    public function test_refund_creates_refund_ledger_row_and_marks_paid_refunded(): void
    {
        $inquiry = $this->confirmedBooking('ledgerrefund@example.com');
        $inquiry->update([
            'amount_paid' => $inquiry->total_amount,
            'fully_paid_at' => now(),
            'payment_method' => 'qrph',
            'paymongo_payment_id' => 'pay_ledger_ref',
        ]);
        // Seed the paid row as the webhook would have.
        Payment::recordPaid($inquiry->refresh(), (string) $inquiry->total_amount, 'qrph', 'pay_ledger_ref', 'cs_ref', Payment::TYPE_FULL);

        Http::fake([
            'api.paymongo.com/v1/refunds' => Http::response(['data' => ['id' => 'rfnd_ledger_1']], 200),
        ]);

        $admin = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($admin)->post(route('admin.inquiries.refund', $inquiry));

        $refund = Payment::where('inquiry_id', $inquiry->id)
            ->where('type', Payment::TYPE_REFUND)->firstOrFail();
        $this->assertSame('rfnd_ledger_1', $refund->provider_refund_id);
        $this->assertSame(Payment::STATUS_REFUNDED, $refund->status);

        $paid = Payment::where('inquiry_id', $inquiry->id)
            ->where('provider_payment_id', 'pay_ledger_ref')->firstOrFail();
        $this->assertSame(Payment::STATUS_REFUNDED, $paid->status);
    }

    public function test_backfill_is_idempotent(): void
    {
        $inquiry = $this->confirmedBooking('ledgerbackfill@example.com');
        $inquiry->update([
            'amount_paid' => $inquiry->total_amount,
            'fully_paid_at' => now(),
            'payment_method' => 'qrph',
            'paymongo_payment_id' => 'pay_ledger_back',
        ]);

        $this->artisan('payments:backfill')->assertSuccessful();
        $this->assertSame(1, Payment::where('inquiry_id', $inquiry->id)->where('type', '!=', Payment::TYPE_REFUND)->count());

        // Second run must not duplicate.
        $this->artisan('payments:backfill')->assertSuccessful();
        $this->assertSame(1, Payment::where('inquiry_id', $inquiry->id)->where('type', '!=', Payment::TYPE_REFUND)->count());
    }

    public function test_ledger_total_matches_summary_for_webhook_payment(): void
    {
        // Gate for WP-1: ledger paid total == inquiries.amount_paid.
        $inquiry = $this->confirmedBooking('ledgergate@example.com');

        $payload = $this->paidPayload($inquiry, 'pay_ledger_gate', (int) round((float) $inquiry->total_amount * 100));
        $json = json_encode($payload);
        $this->postJson(route('payment.webhook'), $payload, ['Paymongo-Signature' => $this->signatureFor($json)])->assertOk();

        $inquiry->refresh();
        $ledgerTotal = (float) Payment::where('inquiry_id', $inquiry->id)
            ->where('type', '!=', Payment::TYPE_REFUND)
            ->where('status', Payment::STATUS_PAID)
            ->sum('amount');

        $this->assertSame((float) $inquiry->amount_paid, $ledgerTotal);
    }
}
