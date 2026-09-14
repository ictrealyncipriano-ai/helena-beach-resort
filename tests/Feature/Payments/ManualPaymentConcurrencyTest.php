<?php

namespace Tests\Feature\Payments;

use App\Models\Cottage;
use App\Models\Inquiry;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Item 5: Inquiry::recordManualPayment() is transactional + locked, so a
 * serialized double-submit can never lose an update or drift the
 * inquiries.* summary away from the payments ledger.
 */
class ManualPaymentConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('cloudflare');
    }

    private function confirmedBooking(string $email, string $total = '3000.00', ?string $deposit = null): Inquiry
    {
        return Inquiry::create([
            'reference_code' => Inquiry::generateReferenceCode(),
            'name' => 'Manual Guest',
            'email' => $email,
            'phone' => '09170000000',
            'check_in' => '2026-08-01',
            'check_out' => '2026-08-03',
            'cottage_id' => Cottage::first()->id,
            'pax' => 2,
            'booking_type' => 'overnight',
            'status' => 'confirmed',
            'total_amount' => $total,
            'deposit_amount' => $deposit,
            'source' => 'booking',
        ]);
    }

    private function ledgerSum(Inquiry $inquiry): float
    {
        return (float) Payment::where('inquiry_id', $inquiry->id)
            ->where('provider', Payment::PROVIDER_MANUAL)
            ->where('status', Payment::STATUS_PAID)
            ->sum('amount');
    }

    public function test_concurrent_double_submit_has_no_lost_update_and_matches_ledger(): void
    {
        $inquiry = $this->confirmedBooking('double@example.com');

        // Two request-scoped instances racing on the same row: each read
        // amount_paid = 0 before either wrote.
        $first = Inquiry::find($inquiry->id);
        $second = Inquiry::find($inquiry->id);

        $first->recordManualPayment('1000.00');
        $second->recordManualPayment('1000.00');

        $inquiry->refresh();

        // Both serialized legs land: no lost update, summary == ledger sum.
        $this->assertSame('2000.00', (string) $inquiry->amount_paid);
        $this->assertSame(2, Payment::where('inquiry_id', $inquiry->id)->count());
        $this->assertSame((float) $inquiry->amount_paid, $this->ledgerSum($inquiry));
        $this->assertFalse($inquiry->isPaid());
    }

    public function test_over_balance_after_locked_reread_is_rejected(): void
    {
        $inquiry = $this->confirmedBooking('overbalance@example.com');

        // Stale instance read when nothing was paid yet.
        $stale = Inquiry::find($inquiry->id);

        // A concurrent settlement lands first: 2500 of 3000 paid.
        Inquiry::find($inquiry->id)->recordManualPayment('2500.00');

        // The stale writer's 1000 no longer fits the locked balance (500).
        try {
            $stale->recordManualPayment('1000.00');
            $this->fail('Expected a ValidationException for the over-balance payment.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('amount', $e->errors());
        }

        $inquiry->refresh();
        $this->assertSame('2500.00', (string) $inquiry->amount_paid);
        $this->assertSame(1, Payment::where('inquiry_id', $inquiry->id)->count());
        $this->assertSame((float) $inquiry->amount_paid, $this->ledgerSum($inquiry));
    }

    public function test_same_idempotency_key_records_only_once(): void
    {
        $inquiry = $this->confirmedBooking('idem@example.com');

        $first = Inquiry::find($inquiry->id)->recordManualPayment('1000.00', Inquiry::METHOD_MANUAL, 'key-abc-123');
        $second = Inquiry::find($inquiry->id)->recordManualPayment('1000.00', Inquiry::METHOD_MANUAL, 'key-abc-123');

        $this->assertFalse($first);
        $this->assertFalse($second);

        $inquiry->refresh();
        $this->assertSame('1000.00', (string) $inquiry->amount_paid);
        $this->assertSame(1, Payment::where('inquiry_id', $inquiry->id)->count());
        $this->assertSame((float) $inquiry->amount_paid, $this->ledgerSum($inquiry));
    }

    public function test_distinct_idempotency_keys_both_record(): void
    {
        $inquiry = $this->confirmedBooking('idemkeys@example.com');

        Inquiry::find($inquiry->id)->recordManualPayment('1000.00', Inquiry::METHOD_MANUAL, 'key-one');
        Inquiry::find($inquiry->id)->recordManualPayment('1000.00', Inquiry::METHOD_MANUAL, 'key-two');

        $inquiry->refresh();
        $this->assertSame('2000.00', (string) $inquiry->amount_paid);
        $this->assertSame(2, Payment::where('inquiry_id', $inquiry->id)->count());
        $this->assertSame((float) $inquiry->amount_paid, $this->ledgerSum($inquiry));
    }

    public function test_deposit_coverage_and_type_classification_recomputed_in_lock(): void
    {
        $inquiry = $this->confirmedBooking('depositclass@example.com', '3000.00', '1500.00');

        $fullyPaid = $inquiry->recordManualPayment('1500.00');

        $this->assertFalse($fullyPaid);
        $inquiry->refresh();
        $this->assertTrue($inquiry->isDepositPaid());
        $this->assertNotNull($inquiry->deposit_paid_at);
        $this->assertNull($inquiry->fully_paid_at);
        $this->assertSame(
            Payment::TYPE_DEPOSIT,
            Payment::where('inquiry_id', $inquiry->id)->firstOrFail()->type
        );

        $fullyPaid = Inquiry::find($inquiry->id)->recordManualPayment('1500.00');

        $this->assertTrue($fullyPaid);
        $inquiry->refresh();
        $this->assertTrue($inquiry->isPaid());
        $this->assertSame(
            Payment::TYPE_BALANCE,
            Payment::where('inquiry_id', $inquiry->id)->orderByDesc('id')->firstOrFail()->type
        );
        $this->assertSame((float) $inquiry->amount_paid, $this->ledgerSum($inquiry));
    }

    public function test_proof_approval_failure_keeps_status_pending_and_writes_no_ledger(): void
    {
        $inquiry = $this->confirmedBooking('prooffail@example.com');
        $inquiry->update([
            'payment_proof_path' => 'payment-proofs/fail.jpg',
            'payment_proof_status' => 'pending',
            'payment_proof_submitted_at' => now(),
        ]);

        $admin = User::factory()->create(['role' => 'super_admin']);

        // Fail the ledger insert AFTER the controller's validate() passed:
        // the APPROVED flag must roll back with the money write.
        Payment::creating(function () {
            if (self::$failLedgerWrite) {
                throw new \RuntimeException('ledger write failed');
            }
        });
        self::$failLedgerWrite = true;

        // The framework converts the bubbled RuntimeException to a 500;
        // what matters is the DB state: proof still pending, no ledger row.
        $this->actingAs($admin)->post(
            route('admin.inquiries.payment-proof.approve', $inquiry),
            ['amount' => 1500]
        )->assertServerError();

        self::$failLedgerWrite = false;

        $inquiry->refresh();
        $this->assertSame('pending', $inquiry->payment_proof_status);
        $this->assertSame('0.00', (string) $inquiry->amount_paid);
        $this->assertSame(0, Payment::where('inquiry_id', $inquiry->id)->count());
    }

    protected static bool $failLedgerWrite = false;
}
