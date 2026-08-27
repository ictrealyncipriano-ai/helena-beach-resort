<?php

if (! function_exists('formatPrice')) {
    /**
     * Format a numeric value as Philippine peso currency.
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
        $numeric = (float) $amount;

        if ($withSymbol) {
            return '₱'.number_format($numeric, $decimals, '.', ',');
        }

        return number_format($numeric, $decimals, '.', '');
    }
}
