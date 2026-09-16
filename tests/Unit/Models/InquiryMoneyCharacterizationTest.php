<?php

namespace Tests\Unit\Models;

use App\Models\Inquiry;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Locked slice characterization: Inquiry monetary methods.
 *
 * In scope (migrate): balanceDue(), amountDueNow(), collectedAmount(),
 * outstandingBalance() — plus Phase 7.3 deposit predicates: hasDeposit(),
 * isDepositPaid(), hasPayments().
 * Pinned only: refundableAmount(), recordManualPayment().
 *
 * All amounts are 2-decimal domain (DB decimal 10,2). 3-decimal in-memory
 * subtraction is intentionally out of scope: old code subtracted in binary
 * float then normalized, Money::sub() normalizes operands first (exact).
 * Likewise, in-memory sub-cent deposit dust (e.g. deposit_amount '0.001',
 * unrepresentable in decimal:2 columns) is unpinned: legacy float sees it
 * as configured while Money normalizes it to zero. No production path
 * persists or decides on such values.
 */
class InquiryMoneyCharacterizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function makeInquiry(array $overrides = []): Inquiry
    {
        return Inquiry::create(array_merge([
            'reference_code' => Inquiry::generateReferenceCode(),
            'name' => 'Money Guest',
            'email' => uniqid('money').'@example.com',
            'status' => Inquiry::STATUS_CONFIRMED,
            'source' => Inquiry::SOURCE_WEBSITE,
            'total_amount' => '5000.00',
        ], $overrides));
    }

    public function test_normal_subtraction(): void
    {
        $inquiry = $this->makeInquiry(['amount_paid' => '1500.00']);

        $this->assertSame('1500.00', $inquiry->collectedAmount());
        $this->assertSame('3500.00', $inquiry->balanceDue());
        $this->assertSame('3500.00', $inquiry->outstandingBalance());
    }

    public function test_rounding_half_up_contract(): void
    {
        // Money anchor preserved through formatPrice display path.
        $this->assertSame('10.01', Money::from('10.005'));
        $this->assertSame(1001, Money::toCentavos('10.005'));

        // 2-decimal domain rounding via collectedAmount (unclamped path).
        $inquiry = $this->makeInquiry();
        $inquiry->amount_paid = '10.005';

        $this->assertSame('10.01', $inquiry->collectedAmount());
    }

    public function test_null_zero_values(): void
    {
        // amount_paid column is NOT NULL (defaults 0); null paid is only
        // reachable in-memory via casts/legacy reads. Exercise both paths.
        $inquiry = $this->makeInquiry(['total_amount' => null, 'amount_paid' => '0.00']);

        $this->assertSame('0.00', $inquiry->collectedAmount());
        $this->assertSame('0.00', $inquiry->balanceDue());
        $this->assertSame('0.00', $inquiry->outstandingBalance());
        $this->assertSame('0.00', $inquiry->amountDueNow());

        $memory = new Inquiry(['total_amount' => null, 'amount_paid' => null]);
        $this->assertSame('0.00', $memory->collectedAmount());
        $this->assertSame('0.00', $memory->balanceDue());
        $this->assertSame('0.00', $memory->outstandingBalance());

        $zero = $this->makeInquiry(['total_amount' => '0.00', 'amount_paid' => '0.00']);

        $this->assertSame('0.00', $zero->collectedAmount());
        $this->assertSame('0.00', $zero->balanceDue());
        $this->assertSame('0.00', $zero->outstandingBalance());
    }

    public function test_overpayment_clamp(): void
    {
        $inquiry = $this->makeInquiry(['total_amount' => '5000.00', 'amount_paid' => '6000.00']);

        $this->assertSame('6000.00', $inquiry->collectedAmount());
        $this->assertSame('0.00', $inquiry->balanceDue());
        $this->assertSame('0.00', $inquiry->outstandingBalance());
    }

    public function test_negative_amount_paid_stays_unclamped_in_collected(): void
    {
        $inquiry = $this->makeInquiry(['total_amount' => '5000.00']);
        $inquiry->amount_paid = '-100.00';

        $this->assertSame('-100.00', $inquiry->collectedAmount());
        $this->assertSame('5100.00', $inquiry->balanceDue());
        $this->assertSame('5100.00', $inquiry->outstandingBalance());
    }

    public function test_deposit_ladder(): void
    {
        $inquiry = $this->makeInquiry(['deposit_amount' => '1500.00', 'amount_paid' => '0.00']);
        $this->assertSame('1500.00', $inquiry->amountDueNow());

        $inquiry->update(['amount_paid' => '500.00']);
        $this->assertSame('1000.00', $inquiry->refresh()->amountDueNow());

        $inquiry->update(['amount_paid' => '1500.00']);
        $this->assertSame('3500.00', $inquiry->refresh()->amountDueNow());
        $this->assertSame($inquiry->balanceDue(), $inquiry->amountDueNow());
    }

    public function test_covered_deposit_via_timestamp_vs_amount(): void
    {
        // Coverage without timestamp satisfies deposit (pinned isDepositPaid logic).
        $covered = $this->makeInquiry(['deposit_amount' => '1500.00', 'amount_paid' => '2000.00']);
        $this->assertNull($covered->deposit_paid_at);
        $this->assertTrue($covered->isDepositPaid());
        $this->assertSame('3000.00', $covered->amountDueNow());

        // Timestamp satisfies deposit even with nothing paid (pinned logic).
        $stamped = $this->makeInquiry([
            'deposit_amount' => '1500.00',
            'amount_paid' => '0.00',
            'deposit_paid_at' => now(),
        ]);
        $this->assertTrue($stamped->isDepositPaid());
        $this->assertSame($stamped->balanceDue(), $stamped->amountDueNow());
        $this->assertSame('5000.00', $stamped->amountDueNow());
    }

    public function test_full_payment(): void
    {
        $inquiry = $this->makeInquiry(['deposit_amount' => '1500.00', 'amount_paid' => '5000.00']);

        $this->assertSame('5000.00', $inquiry->collectedAmount());
        $this->assertSame('0.00', $inquiry->balanceDue());
        $this->assertSame('0.00', $inquiry->outstandingBalance());
        $this->assertSame('0.00', $inquiry->amountDueNow());
    }

    public function test_over_deposit_behavior(): void
    {
        // Validation may allow deposit > total; due-now follows deposit (pinned divergence).
        $inquiry = $this->makeInquiry(['total_amount' => '5000.00', 'deposit_amount' => '6000.00', 'amount_paid' => '0.00']);

        $this->assertSame('6000.00', $inquiry->amountDueNow());
        $this->assertSame('5000.00', $inquiry->balanceDue());
        $this->assertSame('5000.00', $inquiry->outstandingBalance());
    }

    public function test_collected_amount_remains_unclamped(): void
    {
        $over = $this->makeInquiry(['amount_paid' => '6000.00']);
        $this->assertSame('6000.00', $over->collectedAmount());
        $this->assertSame($over->collectedAmount(), $over->refundableAmount());

        $negative = $this->makeInquiry();
        $negative->amount_paid = '-50.00';
        $this->assertSame('-50.00', $negative->collectedAmount());
    }

    public function test_balance_and_outstanding_agree_on_two_decimal_domain(): void
    {
        foreach (['0.00', '1.00', '1500.00', '4999.99', '5000.00', '6000.00'] as $paid) {
            $inquiry = $this->makeInquiry(['total_amount' => '5000.00', 'amount_paid' => $paid]);

            $this->assertSame(
                $inquiry->balanceDue(),
                $inquiry->outstandingBalance(),
                "paid={$paid}"
            );
        }
    }

    /**
     * Phase 7.3 goldens: deposit predicates on the 2-decimal domain.
     * Captured from the legacy float implementation; must stay green
     * through the Money::cmp migration.
     */
    public function test_has_deposit_predicate(): void
    {
        $this->assertFalse($this->makeInquiry(['deposit_amount' => null])->hasDeposit());
        $this->assertFalse($this->makeInquiry(['deposit_amount' => '0.00'])->hasDeposit());
        $this->assertTrue($this->makeInquiry(['deposit_amount' => '0.01'])->hasDeposit());
        $this->assertTrue($this->makeInquiry(['deposit_amount' => '1500.00'])->hasDeposit());
    }

    public function test_is_deposit_paid_predicate(): void
    {
        // No configured deposit is never "deposit paid", even when paid.
        $this->assertFalse($this->makeInquiry(['deposit_amount' => null, 'amount_paid' => '5000.00'])->isDepositPaid());
        $this->assertFalse($this->makeInquiry(['deposit_amount' => '0.00', 'amount_paid' => '5000.00'])->isDepositPaid());

        // Below / at / above the deposit line, without a timestamp.
        $this->assertFalse($this->makeInquiry(['deposit_amount' => '1500.00', 'amount_paid' => '0.00'])->isDepositPaid());
        $this->assertFalse($this->makeInquiry(['deposit_amount' => '1500.00', 'amount_paid' => '1499.99'])->isDepositPaid());
        $this->assertTrue($this->makeInquiry(['deposit_amount' => '1500.00', 'amount_paid' => '1500.00'])->isDepositPaid());
        $this->assertTrue($this->makeInquiry(['deposit_amount' => '1500.00', 'amount_paid' => '2000.00'])->isDepositPaid());

        // Timestamp satisfies the deposit with nothing paid.
        $stamped = $this->makeInquiry([
            'deposit_amount' => '1500.00',
            'amount_paid' => '0.00',
            'deposit_paid_at' => now(),
        ]);
        $this->assertTrue($stamped->isDepositPaid());
    }

    public function test_has_payments_predicate(): void
    {
        $this->assertFalse($this->makeInquiry(['amount_paid' => '0.00'])->hasPayments());
        $this->assertTrue($this->makeInquiry(['amount_paid' => '0.01'])->hasPayments());
        $this->assertTrue($this->makeInquiry(['amount_paid' => '100.00'])->hasPayments());
        $this->assertFalse($this->makeInquiry(['amount_paid' => '-50.00'])->hasPayments());

        $memory = new Inquiry(['amount_paid' => null]);
        $this->assertFalse($memory->hasPayments());
    }
}
