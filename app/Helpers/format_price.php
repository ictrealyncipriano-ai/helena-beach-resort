<?php

use App\Support\Money;

if (! function_exists('formatPrice')) {
    /**
     * Format a numeric value as Philippine peso currency.
     *
     * Money Migration — Phase 2: scale-2 path delegates normalization to
     * App\Support\Money::from() (string-based half-up, no binary float
     * arithmetic). Non-2 $decimals keep the legacy float/number_format
     * fallback byte-for-byte. Negative display preserves '₱-50.00' order.
     *
     * When $withSymbol is false a raw numeric string (no commas, no symbol)
     * is returned — safe for DB storage, validation max rules, and any
     * context where the value will later be cast to (float).
     *
     * @param  int|float|string  $amount
     * @param  int    $decimals  Decimal places (default 2)
     * @param  bool   $withSymbol  Prefix with ₱ (default true)
     * @return string
     */
    function formatPrice(int|float|string $amount, int $decimals = 2, bool $withSymbol = true): string
    {
        if ($decimals !== 2) {
            $numeric = (float) $amount;

            if ($withSymbol) {
                return '₱'.number_format($numeric, $decimals, '.', ',');
            }

            return number_format($numeric, $decimals, '.', '');
        }

        $normalized = Money::from($amount);

        if (! $withSymbol) {
            return $normalized;
        }

        $negative = str_starts_with($normalized, '-');
        $unsigned = $negative ? substr($normalized, 1) : $normalized;
        [$intPart, $fracPart] = array_pad(explode('.', $unsigned, 2), 2, '00');

        $grouped = strrev(implode(',', str_split(strrev($intPart === '' ? '0' : $intPart), 3)));
        $tail = $grouped.'.'.substr(str_pad($fracPart, 2, '0'), 0, 2);

        return $negative ? '₱-'.$tail : '₱'.$tail;
    }
}
