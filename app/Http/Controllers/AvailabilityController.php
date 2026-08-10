<?php

namespace App\Http\Controllers;

use App\Models\Cottage;
use App\Models\CottageDateBlock;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Public, read-only availability lookup for the homepage widget.
 *
 * Returns whether a cottage is free across a requested date range together
 * with the booking rate, without leaking today's admin-only block reasons.
 */
class AvailabilityController extends Controller
{
    public function check(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cottage_id' => ['required', 'integer', Rule::exists('cottages', 'id')->where('is_available', true)],
            'booking_type' => ['required', 'string', 'in:day_tour,overnight'],
            'check_in' => ['required', 'date', 'after_or_equal:today'],
            'check_out' => ['nullable', 'required_if:booking_type,overnight', 'date', 'after:check_in'],
        ]);

        $cottage = Cottage::find($validated['cottage_id']);
        $checkIn = Carbon::parse($validated['check_in']);
        $checkOut = isset($validated['check_out']) ? Carbon::parse($validated['check_out']) : null;

        $range = $this->dateRange($checkIn, $checkOut);

        $blocked = CottageDateBlock::where('cottage_id', $cottage->id)
            ->whereIn('date', $range)
            ->pluck('date')
            ->map(fn ($date) => $date->format('Y-m-d'))
            ->sort()
            ->values();

        $rate = $this->rateForRange($cottage, $validated['booking_type'], $checkIn, $checkOut);

        return response()->json([
            'available' => $blocked->isEmpty(),
            'blocked_dates' => $blocked,
            'rate' => $rate,
            'cottage' => [
                'id' => $cottage->id,
                'name' => $cottage->name,
                'capacity' => $cottage->capacity,
            ],
        ]);
    }

    /**
     * Every calendar date in the range, inclusive of check-in and check-out
     * (a day tour without a check-out covers only its check-in day).
     *
     * @return string[]
     */
    private function dateRange(Carbon $checkIn, ?Carbon $checkOut): array
    {
        $dates = [];
        $cursor = $checkIn->copy();
        $end = $checkOut ?? $checkIn->copy();

        while ($cursor->lte($end)) {
            $dates[] = $cursor->format('Y-m-d');
            $cursor->addDay();
        }

        return $dates;
    }

    /**
     * Applicable rate for the range: the quoted total for day tours, or the
     * per-night rate for overnight stays (peak pricing applied per night).
     *
     * @return array{label: string, amount: string}
     */
    private function rateForRange(Cottage $cottage, string $bookingType, Carbon $checkIn, ?Carbon $checkOut): array
    {
        if ($bookingType === 'day_tour') {
            $amount = $cottage->rateFor($checkIn, 'day_tour');

            return [
                'label' => '₱'.number_format((float) $amount).' / day',
                'amount' => $amount,
            ];
        }

        $nights = $checkOut ? max($checkIn->diffInDays($checkOut), 1) : 1;
        $total = '0.00';

        for ($i = 0; $i < $nights; $i++) {
            $total = number_format(
                (float) $total + (float) $cottage->rateFor($checkIn->copy()->addDays($i), 'overnight'),
                2, '.', ''
            );
        }

        $nightly = $cottage->rateFor($checkIn, 'overnight');

        return [
            'label' => '₱'.number_format((float) $total).' (₱'.number_format((float) $nightly).' / night)',
            'amount' => $total,
        ];
    }
}
