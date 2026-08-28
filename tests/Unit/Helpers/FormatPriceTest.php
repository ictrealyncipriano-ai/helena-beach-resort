<?php

namespace Tests\Unit\Helpers;

use Tests\TestCase;

/**
 * Phase 8.4 — formatPrice() is the shared currency formatter introduced to
 * replace ad-hoc number_format calls. These tests lock in its two modes:
 * display (₱ symbol + thousands separators) and storage (raw numeric string).
 */
class FormatPriceTest extends TestCase
{
    public function test_integer_renders_with_symbol(): void
    {
        $this->assertSame('₱1,200.00', formatPrice(1200));
    }

    public function test_float_renders_rounded_to_two_decimals(): void
    {
        $this->assertSame('₱1,200.50', formatPrice(1200.5));
    }

    public function test_string_input_renders_with_symbol(): void
    {
        $this->assertSame('₱3,000.00', formatPrice('3000'));
    }

    public function test_no_symbol_returns_raw_numeric_string(): void
    {
        // No commas, no symbol: the DB-storage / validation contract.
        $this->assertSame('3000.00', formatPrice(3000, 2, false));
    }

    public function test_no_symbol_supports_custom_decimals(): void
    {
        $this->assertSame('3000.5', formatPrice(3000.5, 1, false));
    }

    public function test_zero_renders_with_symbol(): void
    {
        $this->assertSame('₱0.00', formatPrice(0));
    }

    public function test_zero_renders_raw_with_no_symbol(): void
    {
        $this->assertSame('0.00', formatPrice(0, 2, false));
    }

    public function test_negative_renders_with_symbol(): void
    {
        $this->assertSame('₱-50.00', formatPrice(-50));
    }

    public function test_large_value_groups_thousands(): void
    {
        $this->assertSame('₱1,234,567.89', formatPrice(1234567.89));
    }
}
