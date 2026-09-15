<?php

namespace Tests\Unit\Helpers;

use Tests\TestCase;

/**
 * Money Migration — Phase 2 golden characterization for formatPrice().
 *
 * Freezes the preserved-vs-changed boundary when the scale-2 path delegates
 * to App\Support\Money::from():
 *
 * Preserved: '₱-50.00' negative display order, non-2 $decimals legacy
 * fallback, strict int|float|string signature, display grouping shape.
 *
 * Changed (intentional): scale-2 storage/display normalization is now exact
 * string half-up (no binary float drift); grouped/symbol inputs normalize
 * via Money instead of (float) casting to 0.
 */
class FormatPriceCharacterizationTest extends TestCase
{
    public function test_scale2_storage_is_exact_string_half_up(): void
    {
        $this->assertSame('10.00', formatPrice('10.004', 2, false));
        $this->assertSame('10.01', formatPrice('10.005', 2, false));
        $this->assertSame('-10.01', formatPrice('-10.005', 2, false));
        $this->assertSame('0.30', formatPrice('0.300', 2, false));
    }

    public function test_scale2_display_matches_storage_with_symbol_and_grouping(): void
    {
        $this->assertSame('₱10.01', formatPrice('10.005'));
        $this->assertSame('₱1,234.56', formatPrice('1234.56'));
        $this->assertSame('₱4,800.00', formatPrice('4800.00'));
    }

    public function test_negative_display_order_preserved(): void
    {
        $this->assertSame('₱-50.00', formatPrice(-50));
        $this->assertSame('₱-10.01', formatPrice('-10.005'));
        $this->assertSame('-50.00', formatPrice(-50, 2, false));
    }

    public function test_non2_decimals_keep_legacy_fallback(): void
    {
        $this->assertSame('3000.5', formatPrice(3000.5, 1, false));
        $this->assertSame('₱3,000.5', formatPrice(3000.5, 1, true));
    }

    public function test_grouped_symbol_input_normalizes_via_money(): void
    {
        // Intentional change: legacy (float) cast gave '0.00' / '₱0.00'.
        // No production caller passes grouped/symbol strings.
        $this->assertSame('1234.56', formatPrice('₱1,234.56', 2, false));
        $this->assertSame('₱1,234.56', formatPrice('₱1,234.56'));
    }

    public function test_zero_and_large_values(): void
    {
        $this->assertSame('₱0.00', formatPrice(0));
        $this->assertSame('0.00', formatPrice(0, 2, false));
        $this->assertSame('₱1,234,567.89', formatPrice(1234567.89));
        $this->assertSame('1234567.89', formatPrice(1234567.89, 2, false));
    }
}
