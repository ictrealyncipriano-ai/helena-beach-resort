<?php

namespace App\Http\Controllers;

use App\Models\Cottage;
use App\Models\CottageDateBlock;
use App\Models\Inquiry;
use App\Services\PricingService;
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
    public function check(Request $request, PricingService $pricing): JsonResponse
    {
        $validated = $request->validate([
            'cottage_id' => ['required', 'integer', Rule::exists('cottages', 'id')->where('is_available', true)],
            'booking_type' => ['required', 'string', 'in:' . implode(',', Inquiry::BOOKING_TYPES)],
            'check_in' => ['required', 'date', 'after_or_equal:today'],
            'check_out' => ['nullable', 'required_if:booking_type,' . Inquiry::TYPE_OVERNIGHT, 'date', 'after:check_in'],
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

        $rate = $this->rateForRange($cottage, $validated['booking_type'], $checkIn, $checkOut, $pricing);

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
    private function rateForRange(Cottage $cottage, string $bookingType, Carbon $checkIn, ?Carbon $checkOut, PricingService $pricing): array
    {
        if ($bookingType === Inquiry::TYPE_DAY_TOUR) {
            $amount = $cottage->rateFor($checkIn, Inquiry::TYPE_DAY_TOUR);

            return [
                'label' => formatPrice($amount).' / day',
                'amount' => $amount,
            ];
        }

        $total = $pricing->nightlyTotal($cottage, $checkIn, $checkOut);
        $nightly = $cottage->rateFor($checkIn, Inquiry::TYPE_OVERNIGHT);

        return [
            'label' => formatPrice($total).' ('.formatPrice($nightly).' / night)',
            'amount' => $total,
        ];
    }
}
