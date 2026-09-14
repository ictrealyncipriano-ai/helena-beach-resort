<?php

namespace App\Support;

use App\Models\Inquiry;
use Carbon\Carbon;

/**
 * Single source of truth for which calendar dates a stay blocks.
 *
 * Invariant: overnight stays cover [check_in, check_out) — the check-out
 * day is available for the next booking, so number_of_nights ==
 * number_of_blocked_dates. Day tours cover only [check_in].
 */
class StayDates
{
    /**
     * Calendar dates blocked by a stay, as Y-m-d strings.
     *
     * @param  Carbon|\DateTimeInterface|string|null  $checkIn
     * @param  Carbon|\DateTimeInterface|string|null  $checkOut
     */
    public static function blockedDates(mixed $checkIn, mixed $checkOut = null, mixed $bookingType = null): array
    {
        if (empty($checkIn)) {
            return [];
        }

        $start = self::toCarbon($checkIn);
        if ($start === null) {
            return [];
        }

        // Day tours always block exactly the check-in day.
        if (($bookingType ?? null) === Inquiry::TYPE_DAY_TOUR) {
            return [$start->format('Y-m-d')];
        }

        // Overnight (or unknown type defaulting to overnight): without a
        // check-out there is nothing beyond check-in to block.
        if (empty($checkOut)) {
            return [$start->format('Y-m-d')];
        }

        $end = self::toCarbon($checkOut);
        if ($end === null) {
            return [$start->format('Y-m-d')];
        }

        // Degenerate / invalid range (check-out on or before check-in):
        // keep a single-night hold so validation still sees a block
        // instead of silently passing with zero dates.
        if ($end->lte($start)) {
            return [$start->format('Y-m-d')];
        }

        $dates = [];
        $cursor = $start->copy();

        // Exclusive check-out: [check_in, check_out).
        while ($cursor->lt($end)) {
            $dates[] = $cursor->format('Y-m-d');
            $cursor->addDay();
        }

        return $dates;
    }

    private static function toCarbon(mixed $value): ?Carbon
    {
        if ($value instanceof Carbon) {
            return $value->copy()->startOfDay();
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->startOfDay();
        }

        if (is_string($value) && trim($value) !== '') {
            try {
                return Carbon::parse($value)->startOfDay();
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }
}
