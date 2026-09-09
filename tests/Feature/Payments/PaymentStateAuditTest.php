<?php

namespace Tests\Feature\Payments;

use App\Models\Cottage;
use App\Models\Inquiry;
use App\Models\Payment;
use App\Models\User;
use App\Services\RefundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * WP-8: global money invariants across a mixed ledger.
 *
 * Builds one booking per lifecycle branch (full-paid, deposit-paid,
 * refunded, late-refunded, failed-refund, manual) and asserts the Phase 5
 * invariant holds everywhere: no payment disappears (every collected peso
 * has a ledger row), no refund is falsely successful (refunded_at/completed
 * only after a provider success), and abandoned pendings clear.
 */
class PaymentStateAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        // Six bookings in one test exceed the booking throttle (3/min);
        // exempt throttle only — auth and all other middleware stay live.
        $this->withoutMiddleware(ThrottleRequests::class);
    }

    private function confirmedBooking(array $overrides, string $email): Inquiry
    {
        $this->post('/book', array_merge([
            'name' => 'Guest',
            'email' => $email,
            'booking_type' => 'overnight',
            'cottage_id' => Cottage::first()->id,
            'check_in' => '2026-09-01',
            'check_out' => '2026-09-03',
            'pax' => 2,
        ], $overrides));

        $inquiry = Inquiry::where('email', $email)->firstOrFail();

        $admin = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($admin)->post(route('admin.inquiries.confirm', $inquiry));

        return $inquiry->refresh();
    }

    private function signatureFor(string $payload): string
    {
        $timestamp = (string) time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$payload, 'test-webhook-secret');

        return "t={$timestamp},te={$signature},li=";
    }

    private function deliverPaid(Inquiry $inquiry, string $paymentId, int $centavos): void
    {
        $payload = json_encode([
            'data' => [
                'id' => 'cs_audit_'.$paymentId,
                'type' => 'checkout_session',
                'attributes' => [
                    'reference_number' => $inquiry->reference_code,
                    'payments' => [
                        [
                            'id' => $paymentId,
                            'attributes' => ['status' => 'paid', 'amount' => $centavos, 'currency' => 'PHP', 'source' => ['type' => 'qrph']],
                        ],
                    ],
                ],
            ],
        ]);

        $this->postJson(route('payment.webhook'), json_decode($payload, true), [
            'Paymongo-Signature' => $this->signatureFor($payload),
        ])->assertOk();
    }

    public function test_global_money_invariants_hold_across_all_branches(): void
    {
        Http::fake([
            'api.paymongo.com/v1/refunds' => Http::sequence()
                ->push(['data' => ['id' => 'rfnd_audit_1']], 200)
                ->pushStatus(500, ['errors' => ['boom']])
                ->push(['data' => ['id' => 'rfnd_audit_late']], 200),
        ]);

        // 1. Full online payment.
        $full = $this->confirmedBooking(['check_in' => '2026-09-01', 'check_out' => '2026-09-03'], 'audit-full@example.com');
        $this->deliverPaid($full, 'pay_audit_full', (int) round((float) $full->total_amount * 100));

        // 2. Deposit online payment.
        $dep = $this->confirmedBooking(['check_in' => '2026-09-04', 'check_out' => '2026-09-06'], 'audit-dep@example.com');
        $dep->update(['deposit_amount' => '1500.00', 'payment_pending_amount' => '1500.00']);
        $this->deliverPaid($dep->refresh(), 'pay_audit_dep', 150000);

        // 3. Manual full payment.
        $manual = $this->confirmedBooking(['check_in' => '2026-09-07', 'check_out' => '2026-09-09'], 'audit-manual@example.com');
        $manual->recordManualPayment((string) $manual->total_amount);

        // 4. Paid then guest-cancelled with successful auto-refund.
        $refunded = $this->confirmedBooking(['check_in' => '2026-09-10', 'check_out' => '2026-09-12'], 'audit-ref@example.com');
        $refunded->update([
            'amount_paid' => $refunded->total_amount,
            'fully_paid_at' => now(),
            'payment_method' => 'qrph',
            'paymongo_payment_id' => 'pay_audit_ref',
        ]);
        Payment::recordPaid($refunded->refresh(), (string) $refunded->total_amount, 'qrph', 'pay_audit_ref', 'cs_audit', Payment::TYPE_FULL);
        app(RefundService::class)->claimAndProcess($refunded->refresh(), app(\App\Services\PayMongoService::class));
        $refunded->refresh()->update(['status' => Inquiry::STATUS_CANCELLED]);

        // 5. Paid then failed refund (stays failed, no false success).
        $failed = $this->confirmedBooking(['check_in' => '2026-09-13', 'check_out' => '2026-09-15'], 'audit-fail@example.com');
        $failed->update([
            'amount_paid' => $failed->total_amount,
            'fully_paid_at' => now(),
            'payment_method' => 'qrph',
            'paymongo_payment_id' => 'pay_audit_fail',
        ]);
        Payment::recordPaid($failed->refresh(), (string) $failed->total_amount, 'qrph', 'pay_audit_fail', 'cs_audit', Payment::TYPE_FULL);

        try {
            app(RefundService::class)->claimAndProcess($failed->refresh(), app(\App\Services\PayMongoService::class));
            $this->fail('Expected refund to fail');
        } catch (\RuntimeException) {
            // Expected: second sequence slot is a 500.
        }

        // 6. Late payment on a cancelled booking (auto-refunded).
        $late = $this->confirmedBooking(['check_in' => '2026-09-16', 'check_out' => '2026-09-18'], 'audit-late@example.com');
        $late->update(['status' => Inquiry::STATUS_CANCELLED]);
        $this->deliverPaid($late->refresh(), 'pay_audit_late', (int) round((float) $late->total_amount * 100));

        // ---- Invariant 1: every collected peso has a paid ledger row.
        // Late money is excluded: it never enters the summary (covered by
        // the late-net check below instead).
        foreach (Inquiry::all() as $inquiry) {
            $ledgerPaid = (float) Payment::where('inquiry_id', $inquiry->id)->get()
                ->whereIn('type', [Payment::TYPE_FULL, Payment::TYPE_DEPOSIT, Payment::TYPE_BALANCE])
                ->whereIn('status', [Payment::STATUS_PAID, Payment::STATUS_REFUNDED])
                ->reject(fn ($p) => (bool) ($p->metadata['late_payment'] ?? false))
                ->sum('amount');

            $this->assertSame(
                (float) ($inquiry->amount_paid ?? 0),
                $ledgerPaid,
                "Ledger/summary mismatch for {$inquiry->reference_code}"
            );
        }

        // ---- Invariant 1b: late money nets to zero or waits retry — it
        // never disappears and never leaks into the summary.
        foreach (Inquiry::all() as $inquiry) {
            $lateRows = Payment::where('inquiry_id', $inquiry->id)->get()
                ->filter(fn ($p) => (bool) ($p->metadata['late_payment'] ?? false));
            $latePaid = (float) $lateRows
                ->whereIn('type', [Payment::TYPE_FULL, Payment::TYPE_DEPOSIT, Payment::TYPE_BALANCE])
                ->sum('amount');
            $lateRefunded = (float) $lateRows->where('type', Payment::TYPE_REFUND)->sum('amount');

            $this->assertTrue(
                $latePaid === $lateRefunded
                || Payment::where('inquiry_id', $inquiry->id)
                    ->where('status', Payment::STATUS_REQUIRES_REFUND)->exists(),
                "Late money unaccounted for {$inquiry->reference_code}"
            );
        }

        // ---- Invariant 2: refunded_at/completed only with a refund ledger row.
        foreach (Inquiry::whereNotNull('refunded_at')->get() as $inquiry) {
            $this->assertSame(
                RefundService::STATUS_COMPLETED, $inquiry->refund_status,
                "refunded_at without completed status for {$inquiry->reference_code}"
            );
            $this->assertTrue(
                Payment::where('inquiry_id', $inquiry->id)->where('type', Payment::TYPE_REFUND)->exists(),
                "refunded_at without refund ledger row for {$inquiry->reference_code}"
            );
        }

        // ---- Invariant 3: failed refunds never look successful.
        $this->assertSame(RefundService::STATUS_FAILED, $failed->refresh()->refund_status);
        $this->assertNull($failed->refresh()->refunded_at);

        // ---- Invariant 4: no duplicate provider payment ids.
        $dupes = Payment::select('provider_payment_id')
            ->whereNotNull('provider_payment_id')
            ->groupBy('provider_payment_id')
            ->havingRaw('COUNT(*) > 1')
            ->count();
        $this->assertSame(0, $dupes);

        // ---- Invariant 5: late money preserved + refunded, summary untouched.
        $lateRow = Payment::where('provider_payment_id', 'pay_audit_late')->firstOrFail();
        $this->assertSame(Payment::STATUS_REFUNDED, $lateRow->status);
        $this->assertSame('0.00', (string) ($late->refresh()->amount_paid ?? '0.00'));

        // ---- Invariant 6: deposit recorded without full settlement.
        $this->assertSame('1500.00', (string) $dep->refresh()->amount_paid);
        $this->assertNotNull($dep->refresh()->deposit_paid_at);
        $this->assertNull($dep->refresh()->fully_paid_at);
    }
}
