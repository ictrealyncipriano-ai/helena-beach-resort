<?php

namespace Tests\Unit\Models;

use App\Models\PromoCode;
use Tests\TestCase;

/**
 * Money Migration — Phase 4 golden characterization for
 * PromoCode::discountFor().
 *
 * Pure-unit (no database): in-memory PromoCode models pin exact string
 * discount math. Percent discounts route through Money::mulPct(), fixed
 * discounts through Money::from(), the min() cap through Money::cmp(), and
 * the max(0, ...) subtotal clamp is preserved.
 *
 * Out of scope: valueLabel() display and findUsable() min_amount comparison.
 */
class PromoCodeCharacterizationTest extends TestCase
{
    private function promo(string $type, mixed $value): PromoCode
    {
        return new PromoCode(['code' => 'TEST', 'type' => $type, 'value' => $value]);
    }

    public function test_percent_uses_exact_string_math(): void
    {
        // 10% of 333.33 = 33.333 exact -> half-up down to '33.33'.
        $this->assertSame('33.33', $this->promo('percent', 10)->discountFor('333.33'));

        // 50% of 2500.01 = 1250.005 exact -> half-up up to '1250.01'.
        $this->assertSame('1250.01', $this->promo('percent', 50)->discountFor('2500.01'));

        // Fractional rate: 7.5% of 100.00 = 7.50 exact.
        $this->assertSame('7.50', $this->promo('percent', 7.5)->discountFor('100.00'));
    }

    public function test_fixed_discount_returns_exact_amount(): void
    {
        $this->assertSame('10.00', $this->promo('fixed', 10)->discountFor('100.00'));
        $this->assertSame('10.01', $this->promo('fixed', '10.005')->discountFor('100.00'));
    }

    public function test_discount_never_exceeds_subtotal(): void
    {
        $this->assertSame('100.00', $this->promo('fixed', 5000)->discountFor('100.00'));

        // 150% percent is capped at the subtotal, matching min() semantics.
        $this->assertSame('100.00', $this->promo('percent', 150)->discountFor('100.00'));
    }

    public function test_negative_and_empty_subtotals_clamp_to_zero_base(): void
    {
        $this->assertSame('0.00', $this->promo('fixed', 10)->discountFor('-5'));
        $this->assertSame('0.00', $this->promo('fixed', 10)->discountFor(null));
        $this->assertSame('0.00', $this->promo('fixed', 10)->discountFor(''));
        $this->assertSame('0.00', $this->promo('percent', 10)->discountFor(null));
    }

    public function test_extra_decimal_subtotal_normalizes_before_percent(): void
    {
        // '10.005' normalizes to '10.01'; 10% of 10.01 = 1.001 -> '1.00'.
        $this->assertSame('1.00', $this->promo('percent', 10)->discountFor('10.005'));
    }
}
