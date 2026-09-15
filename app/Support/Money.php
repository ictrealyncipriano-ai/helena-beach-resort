<?php

namespace App\Support;

/**
 * Money Migration — Phase 1: string-based PHP-peso value object (helpers only).
 *
 * Representation contract: every amount is a decimal string with exactly two
 * fractional digits (e.g. '1500.00', '-10.01', '0.30'). No floating-point
 * arithmetic is used anywhere in this class: values are parsed as strings
 * and all math goes through BCMath (bcadd / bcsub / bcmul / bcdiv / bccomp).
 *
 * Rounding contract: inputs with more than two fractional digits are reduced
 * with half-up to two places on the magnitude (third fractional digit of
 * '5' or greater rounds the cents up). This is deterministic for the
 * existing two-decimal domain and pins the edge behaviour for later phases.
 *
 * Contract anchor: toCentavos('10.005') === 1001, because '10.005' first
 * becomes '10.01' under the half-up rule above, and 10.01 pesos is 1001
 * centavos. Later phases must preserve this exact outcome.
 *
 * Parsing contract: null, '' and whitespace-only become '0.00'. Grouping
 * commas, peso signs and surrounding spaces are stripped, so inputs such as
 * '₱1,234.56' normalise to '1234.56'. A single leading '+' or '-' prefix is
 * honoured ('+5' becomes '5.00'). Anything else that is not a plain decimal
 * (multiple dots, letters, a lone sign) deterministically becomes '0.00'.
 * Negative zero of any form ('-0', '-0.00', '-0.004') normalises to '0.00'.
 */
final class Money
{
    /**
     * Normalise int|float|string|null input to exact '0.00'-form string.
     */
    public static function from(mixed $value): string
    {
        if ($value === null) {
            return '0.00';
        }

        if (is_bool($value)) {
            return $value ? '1.00' : '0.00';
        }

        if (is_int($value)) {
            return self::normalizeDecimalString((string) $value);
        }

        if (is_float($value)) {
            return self::normalizeDecimalString(self::floatToDecimalString($value));
        }

        if (is_string($value)) {
            return self::normalizeDecimalString($value);
        }

        if (is_object($value) && method_exists($value, '__toString')) {
            return self::normalizeDecimalString((string) $value->__toString());
        }

        return '0.00';
    }

    /**
     * Exact string addition, scale 2.
     */
    public static function add(string $a, string $b): string
    {
        $sum = bcadd(self::from($a), self::from($b), 2);

        return self::normalizeZero($sum);
    }

    /**
     * Exact string subtraction, scale 2.
     */
    public static function sub(string $a, string $b): string
    {
        $diff = bcsub(self::from($a), self::from($b), 2);

        return self::normalizeZero($diff);
    }

    /**
     * Percentage of an amount with explicit half-up to two places.
     *
     * Example: mulPct('333.33', 10) === '33.33' (exact 33.333 rounds down),
     * and mulPct('2500.01', 50) === '1250.01' (exact 1250.005 rounds up).
     *
     * @param int|float|string $pct Percentage such as 10, 7.5 or '12.25'.
     */
    public static function mulPct(string $amount, int|float|string $pct): string
    {
        $base = self::from($amount);
        $rate = self::parsePctString($pct);

        $raw = bcdiv(bcmul($base, $rate, 10), '100', 10);

        return self::roundHalfUp2($raw);
    }

    /**
     * Three-way comparison via bccomp at scale 2: -1, 0 or 1.
     */
    public static function cmp(string $a, string $b): int
    {
        return bccomp(self::from($a), self::from($b), 2);
    }

    /**
     * Convert an amount to integer centavos with string half-up to cents.
     *
     * CONTRACT: toCentavos('10.005') === 1001. The input first normalises to
     * '10.01' (third fractional digit '5' rounds the cents up), and 10.01
     * pesos equals 1001 centavos. Later migration phases must preserve this
     * exact string half-up outcome and must not reintroduce binary
     * floating-point handling here.
     */
    public static function toCentavos(mixed $value): int
    {
        $normalized = $value instanceof self ? '0.00' : self::from($value);

        $negative = str_starts_with($normalized, '-');
        $unsigned = $negative ? substr($normalized, 1) : $normalized;
        [$intPart, $fracPart] = array_pad(explode('.', $unsigned, 2), 2, '00');

        $cents = bcadd(bcmul($intPart === '' ? '0' : $intPart, '100'), substr(str_pad($fracPart, 2, '0'), 0, 2));

        if ($negative && bccomp($cents, '0') !== 0) {
            $cents = '-' . $cents;
        }

        return (int) $cents;
    }

    /**
     * Human display: '₱1,234.56' with symbol, plain '1234.56' without.
     * The symbol-less form is the bare normalized amount with no thousands
     * grouping. Grouping itself is pure string work so display never touches
     * binary floating point. Negatives render as '-₱1,234.56' / '-1234.56'.
     */
    public static function format(string $amount, bool $withSymbol = true): string
    {
        $normalized = self::from($amount);

        if (! $withSymbol) {
            return $normalized;
        }

        $negative = str_starts_with($normalized, '-');
        $unsigned = $negative ? substr($normalized, 1) : ltrim($normalized, '+');
        [$intPart, $fracPart] = array_pad(explode('.', $unsigned, 2), 2, '00');

        $grouped = strrev(implode(',', str_split(strrev($intPart === '' ? '0' : $intPart), 3)));

        return ($negative ? '-' : '') . '₱' . $grouped . '.' . substr(str_pad($fracPart, 2, '0'), 0, 2);
    }

    /**
     * Strip grouping/formatting characters and reduce to '0.00' form with
     * half-up to two places. Pure string parsing plus BCMath carry only.
     */
    private static function normalizeDecimalString(string $raw): string
    {
        $cleaned = str_replace(['₱', ',', ' ', "\t", "\n", "\r"], '', trim($raw));

        if ($cleaned === '' || $cleaned === '+' || $cleaned === '-' || $cleaned === '.' || $cleaned === '+.' || $cleaned === '-.') {
            return '0.00';
        }

        $negative = false;
        $first = $cleaned[0];
        if ($first === '+' || $first === '-') {
            $negative = $first === '-';
            $cleaned = substr($cleaned, 1);
            if ($cleaned === '' || $cleaned[0] === '+' || $cleaned[0] === '-') {
                return '0.00';
            }
        }

        $parts = explode('.', $cleaned);
        if (count($parts) > 2) {
            return '0.00';
        }

        $intPart = $parts[0];
        $fracPart = $parts[1] ?? '';

        if ($intPart === '') {
            $intPart = '0';
        }

        if (! ctype_digit($intPart) || ($fracPart !== '' && ! ctype_digit($fracPart))) {
            return '0.00';
        }

        $intPart = ltrim($intPart, '0');
        if ($intPart === '') {
            $intPart = '0';
        }

        return self::applyHalfUp2($negative, $intPart, $fracPart);
    }

    /**
     * Reduce an already-numeric decimal string (BCMath output) to '0.00'
     * form with half-up to two places.
     */
    private static function roundHalfUp2(string $decimal): string
    {
        $decimal = trim($decimal);

        if ($decimal === '' || $decimal === '+' || $decimal === '-' || $decimal === '.') {
            return '0.00';
        }

        $negative = str_starts_with($decimal, '-');
        $unsigned = ltrim($decimal, '+-');

        $parts = explode('.', $unsigned);
        if (count($parts) > 2) {
            return '0.00';
        }

        $intPart = $parts[0] === '' ? '0' : $parts[0];
        $fracPart = $parts[1] ?? '';

        if (! ctype_digit($intPart) || ($fracPart !== '' && ! ctype_digit($fracPart))) {
            return '0.00';
        }

        $intPart = ltrim($intPart, '0');
        if ($intPart === '') {
            $intPart = '0';
        }

        return self::applyHalfUp2($negative, $intPart, $fracPart);
    }

    /**
     * Shared half-up core: round the magnitude to two places, where a third
     * fractional digit of '5' or greater carries one cent upward.
     */
    private static function applyHalfUp2(bool $negative, string $intPart, string $fracPart): string
    {
        $firstTwo = substr(str_pad($fracPart, 2, '0'), 0, 2);
        $roundUp = strlen($fracPart) > 2 && $fracPart[2] >= '5';

        $totalCents = bcadd(bcmul($intPart, '100'), $firstTwo);
        if ($roundUp) {
            $totalCents = bcadd($totalCents, '1');
        }

        if (bccomp($totalCents, '0') === 0) {
            return '0.00';
        }

        $intOut = bcdiv($totalCents, '100', 0);
        $remOut = str_pad(bcmod($totalCents, '100'), 2, '0', STR_PAD_LEFT);

        return ($negative ? '-' : '') . $intOut . '.' . $remOut;
    }

    /**
     * Parse a percentage to its exact decimal string without any
     * floating-point math. Grouping commas, spaces, peso and percent signs
     * are stripped; full fractional precision is preserved for the caller.
     *
     * @param int|float|string $pct
     */
    private static function parsePctString(int|float|string $pct): string
    {
        if (is_int($pct)) {
            $raw = (string) $pct;
        } elseif (is_float($pct)) {
            $raw = self::floatToDecimalString($pct);
        } else {
            $raw = $pct;
        }

        $cleaned = str_replace(['₱', ',', ' ', "\t", "\n", "\r", '%'], '', trim($raw));

        if ($cleaned === '' || $cleaned === '+' || $cleaned === '-' || $cleaned === '.') {
            return '0';
        }

        $sign = '';
        if ($cleaned[0] === '+' || $cleaned[0] === '-') {
            $sign = $cleaned[0] === '-' ? '-' : '';
            $cleaned = substr($cleaned, 1);
            if ($cleaned === '' || $cleaned[0] === '+' || $cleaned[0] === '-') {
                return '0';
            }
        }

        $parts = explode('.', $cleaned);
        if (count($parts) > 2) {
            return '0';
        }

        $intPart = $parts[0] === '' ? '0' : $parts[0];
        $fracPart = $parts[1] ?? '';

        if (! ctype_digit($intPart) || ($fracPart !== '' && ! ctype_digit($fracPart))) {
            return '0';
        }

        $intPart = ltrim($intPart, '0');
        if ($intPart === '') {
            $intPart = '0';
        }

        $rate = $fracPart === '' ? $intPart : $intPart . '.' . $fracPart;

        if ($rate === '0' || preg_match('/^0(\.0*)?$/', $rate) === 1) {
            return '0';
        }

        return $sign . $rate;
    }

    /**
     * Render a float as its shortest decimal string, expanding scientific
     * notation with string-only digit shifting so no binary math leaks in.
     */
    private static function floatToDecimalString(float $value): string
    {
        $text = (string) $value;

        if (stripos($text, 'e') === false) {
            return $text;
        }

        if (preg_match('/^([+-]?)(\d*)(?:\.(\d*))?[eE]([+-]?\d+)$/', $text, $m) !== 1) {
            return '0';
        }

        $sign = $m[1] === '-' ? '-' : '';
        $intDigits = $m[2];
        $fracDigits = $m[3] ?? '';
        $exp = (int) $m[4];
        $digits = $intDigits . $fracDigits;

        $digits = ltrim($digits, '0');
        if ($digits === '') {
            return '0';
        }

        $pointPos = strlen($intDigits) + $exp;

        if ($pointPos <= 0) {
            return $sign . '0.' . str_repeat('0', -$pointPos) . $digits;
        }

        if ($pointPos >= strlen($digits)) {
            return $sign . $digits . str_repeat('0', $pointPos - strlen($digits));
        }

        return $sign . substr($digits, 0, $pointPos) . '.' . substr($digits, $pointPos);
    }

    /**
     * Collapse BCMath signed zero variants ('-0.00') to canonical '0.00'.
     */
    private static function normalizeZero(string $amount): string
    {
        if (bccomp($amount, '0', 2) === 0) {
            return '0.00';
        }

        return $amount;
    }
}
