<?php

namespace Tests\Unit\Support;

use App\Support\Money;
use Tests\TestCase;

/**
 * Money Migration — Phase 1 golden characterization.
 *
 * Freezes the string-based 2-decimal contract that later phases must
 * preserve. No database, no callers, no schema: pure Money behaviour only.
 */
class MoneyCharacterizationTest extends TestCase
{
    public function test_from_normalizes_common_inputs(): void
    {
        $this->assertSame('0.00', Money::from(null));
        $this->assertSame('0.00', Money::from(''));
        $this->assertSame('0.00', Money::from('   '));
        $this->assertSame('1500.00', Money::from(1500));
        $this->assertSame('1500.50', Money::from(1500.5));
        $this->assertSame('333.33', Money::from('333.33'));
        $this->assertSame('5.00', Money::from('+5'));
        $this->assertSame('-5.00', Money::from('-5'));
        $this->assertSame('0.00', Money::from('-0'));
        $this->assertSame('0.00', Money::from('-0.00'));
    }

    public function test_from_strips_symbols_and_grouping(): void
    {
        $this->assertSame('1234.56', Money::from('₱1,234.56'));
        $this->assertSame('1234.50', Money::from('  +1,234.5 '));
        $this->assertSame('-1234.56', Money::from('-₱1,234.56'));
        $this->assertSame('0.30', Money::from('0.300'));
    }

    public function test_from_half_up_rounding_past_two_places(): void
    {
        $this->assertSame('10.00', Money::from('10.004'));
        $this->assertSame('10.01', Money::from('10.005'));
        $this->assertSame('10.01', Money::from('10.006'));
        $this->assertSame('10.00', Money::from('10.0049'));
        $this->assertSame('-10.01', Money::from('-10.005'));
        $this->assertSame('10.00', Money::from('9.995'));
    }

    public function test_add_avoids_binary_float_drift(): void
    {
        // Classic binary floating-point trap: 0.1 + 0.2 !== 0.3 as floats.
        $this->assertSame('0.30', Money::add('0.10', '0.20'));
        $this->assertSame('4800.00', Money::add(Money::add('1500.00', '1800.00'), '1500.00'));
        $this->assertSame('4500.00', Money::add(Money::add('1500.00', '1500.00'), '1500.00'));
    }

    public function test_three_night_mixed_rate_sum(): void
    {
        $total = Money::add(Money::add(Money::from('1500'), Money::from('1800')), Money::from('1500'));

        $this->assertSame('4800.00', $total);
        $this->assertSame(480000, Money::toCentavos($total));
    }

    public function test_sub_basics(): void
    {
        $this->assertSame('0.99', Money::sub('1.00', '0.01'));
        $this->assertSame('-0.01', Money::sub('0.00', '0.01'));
        $this->assertSame('0.00', Money::sub('1500.00', '1500.00'));
    }

    public function test_mul_pct_golden_cases(): void
    {
        // 10% of 333.33 = 33.333 exact -> half-up down to '33.33'.
        $this->assertSame('33.33', Money::mulPct('333.33', 10));
        $this->assertSame('33.33', Money::mulPct('333.33', '10'));
        $this->assertSame('33.33', Money::mulPct('333.33', 10.0));

        // 50% of 2500.01 = 1250.005 exact -> half-up up to '1250.01'.
        $this->assertSame('1250.01', Money::mulPct('2500.01', 50));
        $this->assertSame('1250.01', Money::mulPct('2500.01', '50'));

        // Fractional rate sanity: 7.5% of 100.00 = 7.50 exact.
        $this->assertSame('7.50', Money::mulPct('100.00', 7.5));
    }

    public function test_to_centavos_string_half_up_contract(): void
    {
        // CONTRACT (later phases must preserve): '10.005' first becomes
        // '10.01' under string half-up, hence 1001 centavos — never 1000.
        $this->assertSame('10.01', Money::from('10.005'));
        $this->assertSame(1001, Money::toCentavos('10.005'));

        $this->assertSame(1000, Money::toCentavos('10.004'));
        $this->assertSame(480000, Money::toCentavos('4800.00'));
        $this->assertSame(450000, Money::toCentavos('4500.00'));
        $this->assertSame(0, Money::toCentavos(null));
        $this->assertSame(0, Money::toCentavos(''));
        $this->assertSame(-1, Money::toCentavos('-0.01'));
    }

    public function test_invoice_aggregation_three_lines(): void
    {
        $total = Money::add(Money::add('1500.00', '1500.00'), '1500.00');

        $this->assertSame('4500.00', $total);
        $this->assertSame(450000, Money::toCentavos($total));
        $this->assertSame('₱4,500.00', Money::format($total));
    }

    public function test_cmp_three_way(): void
    {
        $this->assertSame(0, Money::cmp('1.00', '1.00'));
        $this->assertSame(1, Money::cmp('1.01', '1.00'));
        $this->assertSame(-1, Money::cmp('0.99', '1.00'));
        $this->assertSame(0, Money::cmp('1.00', '1.004'));
        $this->assertSame(-1, Money::cmp('1.00', '1.005'));
    }

    public function test_format_basics(): void
    {
        $this->assertSame('₱1,234.56', Money::format('1234.56'));
        $this->assertSame('1234.56', Money::format('1234.56', false));
        $this->assertSame('₱4,800.00', Money::format('4800.00'));
        $this->assertSame('4800.00', Money::format('4800.00', false));
        $this->assertSame('-₱1,234.56', Money::format('-1234.56'));
        $this->assertSame('-1234.56', Money::format('-1234.56', false));
        $this->assertSame('₱0.00', Money::format('not-a-number'));
    }
}
