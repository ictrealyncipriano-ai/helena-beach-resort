<?php

namespace App\Services;

use App\Models\Inquiry;
use App\Models\SiteSetting;
use Illuminate\Support\Carbon;

/**
 * P1.1: tiered cancellation policy.
 *
 * Single source for "how much of the collected money is refunded when a
 * booking is cancelled". Tiers come from the `cancellation_policy_json`
 * setting; anything missing or malformed falls back to the locked default
 * (168h/100, 72h/50, 24h/0) so bad DB values fail safely.
 *
 * Quote basis is the collected amount (refundableAmount), never the total:
 * an unpaid booking refunds 0, a deposit-only settlement refunds the
 * deposit share.
 */
class CancellationPolicy
{
    public const SETTING_KEY = 'cancellation_policy_json';

    /** @return array{tiers: array<int, array{hours_before: int, refund_pct: int}>, default_pct: int} */
    public static function tiers(): array
    {
        $fallback = [
            'tiers' => [
                ['hours_before' => 168, 'refund_pct' => 100],
                ['hours_before' => 72, 'refund_pct' => 50],
                ['hours_before' => 24, 'refund_pct' => 0],
            ],
            'default_pct' => 0,
        ];

        $raw = trim((string) SiteSetting::getValue(self::SETTING_KEY, ''));

        if ($raw === '') {
            return $fallback;
        }

        $decoded = json_decode($raw, true);

        if (! is_array($decoded) || ! isset($decoded['tiers']) || ! is_array($decoded['tiers'])) {
            return $fallback;
        }

        $tiers = [];

        foreach ($decoded['tiers'] as $tier) {
            if (! is_array($tier) || ! isset($tier['hours_before'], $tier['refund_pct'])) {
                continue;
            }

            $hours = (int) $tier['hours_before'];
            $pct = (int) $tier['refund_pct'];

            if ($hours < 0 || $pct < 0 || $pct > 100) {
                continue;
            }

            $tiers[] = ['hours_before' => $hours, 'refund_pct' => $pct];
        }

        if ($tiers === []) {
            return $fallback;
        }

        usort($tiers, fn ($a, $b) => $b['hours_before'] <=> $a['hours_before']);

        $default = isset($decoded['default_pct']) ? (int) $decoded['default_pct'] : 0;
        $default = max(0, min(100, $default));

        return ['tiers' => $tiers, 'default_pct' => $default];
    }

    /**
     * @return array{pct: int, hours_before: int, collected: string, refund_amount: string, forfeit_amount: string}
     */
    public static function quote(Inquiry $inquiry, ?Carbon $now = null): array
    {
        $now = $now ?? now();
        $collected = (float) ($inquiry->amount_paid ?? 0);

        if ($collected <= 0 || ! $inquiry->check_in) {
            return self::result(0, 0, $collected);
        }

        $hoursBefore = (int) $now->diffInHours($inquiry->check_in, false);

        if ($hoursBefore < 0) {
            return self::result(0, $hoursBefore, $collected);
        }

        $policy = self::tiers();

        foreach ($policy['tiers'] as $tier) {
            if ($hoursBefore >= $tier['hours_before']) {
                return self::result($tier['refund_pct'], $hoursBefore, $collected);
            }
        }

        return self::result($policy['default_pct'], $hoursBefore, $collected);
    }

    /** @return array{pct: int, hours_before: int, collected: string, refund_amount: string, forfeit_amount: string} */
    private static function result(int $pct, int $hoursBefore, float $collected): array
    {
        $refund = round($collected * $pct / 100, 2);
        $forfeit = round($collected - $refund, 2);

        return [
            'pct' => $pct,
            'hours_before' => $hoursBefore,
            'collected' => number_format($collected, 2, '.', ''),
            'refund_amount' => number_format($refund, 2, '.', ''),
            'forfeit_amount' => number_format($forfeit, 2, '.', ''),
        ];
    }
}
