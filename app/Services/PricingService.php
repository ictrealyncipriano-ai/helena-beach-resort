<?php

namespace App\Services;

use App\Models\Cottage;
use App\Models\Inquiry;
use App\Models\PromoCode;
use Carbon\Carbon;

/**
 * Single source of truth for all price calculations.
 * Replaces the night-by-night accumulation loops previously duplicated
 * across InquiryService, AvailabilityController, and InvoiceController.
 */
class PricingService
{
    /**
     * Total booking amount for a date range, peak-aware per night.
     *
     * @return string|null  Formatted total (e.g. "6000.00"), or null when data is incomplete.
     */
    public function calculateTotal(Cottage $cottage, ?Carbon $checkIn, ?Carbon $checkOut, string $type): ?string
    {
        if ($type === Inquiry::TYPE_DAY_TOUR) {
            if (! $checkIn) {
                return null;
            }

            return $cottage->rateFor($checkIn, Inquiry::TYPE_DAY_TOUR);
        }

        if ($type === Inquiry::TYPE_OVERNIGHT && $checkIn && $checkOut) {
            return $this->nightlyTotal($cottage, $checkIn, $checkOut);
        }

        return null;
    }

    /**
     * Sum of peak-aware per-night rates for an overnight range.
     */
    public function nightlyTotal(Cottage $cottage, Carbon $checkIn, Carbon $checkOut): string
    {
        $nights = max($checkIn->diffInDays($checkOut), 1);
        $total = '0.00';

        for ($i = 0; $i < $nights; $i++) {
            $total = number_format(
                (float) $total + (float) $cottage->rateFor($checkIn->copy()->addDays($i), Inquiry::TYPE_OVERNIGHT),
                2, '.', ''
            );
        }

        return $total;
    }

    /**
     * Per-night breakdown for invoice line items.
     *
     * @return array<int, array{date: string, rate: string}>
     */
    public function nightlyBreakdown(Cottage $cottage, Carbon $checkIn, Carbon $checkOut): array
    {
        $nights = max($checkIn->diffInDays($checkOut), 1);
        $breakdown = [];

        for ($i = 0; $i < $nights; $i++) {
            $night = $checkIn->copy()->addDays($i);
            $breakdown[] = [
                'date' => $night->format('M d, Y'),
                'rate' => $cottage->rateFor($night, Inquiry::TYPE_OVERNIGHT),
            ];
        }

        return $breakdown;
    }

    /**
     * Apply a promo discount to a subtotal.
     *
     * @return array{total: string|null, discount: string, promo: \App\Models\PromoCode|null}
     */
    public function applyDiscount(?string $subtotal, ?PromoCode $promo): array
    {
        if (! $promo || $subtotal === null) {
            return ['total' => $subtotal, 'discount' => '0.00', 'promo' => null];
        }

        $discount = $promo->discountFor($subtotal);
        $total = number_format(max(0, (float) $subtotal - (float) $discount), 2, '.', '');

        return ['total' => $total, 'discount' => $discount, 'promo' => $promo];
    }
}
