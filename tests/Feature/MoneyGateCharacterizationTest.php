<?php

namespace Tests\Feature;

use App\Models\Inquiry;
use App\Models\Payment;
use App\Support\Money;
use Tests\TestCase;

/**
 * Slice 3 — payment money gates (characterization-first).
 *
 * Pins the behavior of every float-based money predicate before migration
 * to Money::cmp/sub: Payment::classifyType(), the legacy-sweep epsilon
 * equality, and the admin deposit/balance/refund gates. All inputs live in
 * the two-decimal domain, where strict float orderings provably agree with
 * bccomp — these tests lock that agreement so the migration must be a
 * no-op behaviorally.
 *
 * Deliberately NOT covered here (separate concerns, documented):
 * - Cottage::ratesMap() casts: JSON numeric boundary for date-picker JS
 *   doing Number() arithmetic (@js($rates) in book/booking-modify views).
 * - format_price()/PromoCode display formatting and image dimensions.
 */
class MoneyGateCharacterizationTest extends TestCase
{
    /**
     * Float-vs-Money agreement on two-decimal strings, including classic
     * binary traps. Every pair must order identically under both.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('agreementPairs')]
    public function test_float_ordering_agrees_with_money_cmp(string $a, string $b, int $expected): void
    {
        $this->assertSame($expected, Money::cmp($a, $b));
        $this->assertSame($expected, (float) $a <=> (float) $b);

        // Sign gates on non-negative amounts can never flip either.
        $this->assertSame(Money::cmp($a, '0.00') > 0, (float) $a > 0);
        $this->assertSame(Money::cmp($a, '0.00') <= 0, (float) $a <= 0);
    }

    public static function agreementPairs(): array
    {
        return [
            'zero vs zero' => ['0.00', '0.00', 0],
            'centavo vs zero' => ['0.01', '0.00', 1],
            'binary trap 0.10 vs 0.20' => ['0.10', '0.20', -1],
            'binary trap 0.30 sum' => ['0.30', '0.30', 0],
            'third-decimal trap' => ['2.67', '2.68', -1],
            'large amounts' => ['1234567.89', '1234567.88', 1],
            'typical rate' => ['2000.00', '1500.00', 1],
            'deposit boundary equal' => ['500.00', '500.00', 0],
            'deposit boundary over' => ['500.01', '500.00', 1],
        ];
    }

    public function test_sweep_epsilon_agrees_with_exact_equality(): void
    {
        // Legacy sweep clears when abs(pending - total) < 0.005. On the
        // decimal(10,2) domain, distinct values differ by >= 0.01, so the
        // epsilon fires exactly when the values are exactly equal.
        $equal = ['4000.00', '4000.00'];
        $split = ['1500.00', '4000.00'];

        $this->assertTrue(abs((float) $equal[0] - (float) $equal[1]) < 0.005);
        $this->assertSame(0, Money::cmp($equal[0], $equal[1]));

        $this->assertFalse(abs((float) $split[0] - (float) $split[1]) < 0.005);
        $this->assertNotSame(0, Money::cmp($split[0], $split[1]));
    }

    private function locked(array $attrs): Inquiry
    {
        return new Inquiry(array_merge([
            'deposit_amount' => null,
        ], $attrs));
    }

    public function test_classify_unpaid_full_payment(): void
    {
        $this->assertSame(
            Payment::TYPE_FULL,
            Payment::classifyType($this->locked(['amount_paid' => '0.00']), true, false)
        );
        $this->assertSame(
            Payment::TYPE_FULL,
            Payment::classifyType($this->locked(['amount_paid' => null]), true, false)
        );
    }

    public function test_classify_paid_full_payment_is_balance(): void
    {
        foreach (['0.01', '0.10', '1999.99'] as $paid) {
            $this->assertSame(
                Payment::TYPE_BALANCE,
                Payment::classifyType($this->locked(['amount_paid' => $paid]), true, false),
                "paid {$paid} must classify as balance"
            );
        }
    }

    public function test_classify_covered_deposit(): void
    {
        $inquiry = $this->locked(['amount_paid' => '0.00', 'deposit_amount' => '500.00']);

        $this->assertSame(Payment::TYPE_DEPOSIT, Payment::classifyType($inquiry, false, true));
    }

    public function test_classify_falls_back_to_balance(): void
    {
        $this->assertSame(
            Payment::TYPE_BALANCE,
            Payment::classifyType($this->locked(['amount_paid' => '0.00']), false, false)
        );
    }
}
