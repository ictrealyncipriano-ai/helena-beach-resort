<?php

namespace App\Services;

use App\Models\Inquiry;
use App\Support\Money;
use Illuminate\Support\Facades\DB;

/**
 * Guest-initiated schedule changes. The old blocks are released and the new
 * ones held inside one transaction, so a conflict on the new dates leaves
 * the original booking (and its hold) untouched.
 */
class BookingModificationService
{
    /**
     * Apply a guest-initiated schedule change inside one transaction.
     */
    public function apply(
        Inquiry $inquiry,
        array $validated,
        array $original,
        bool $wasConfirmed,
        InquiryService $inquiryService
    ): Inquiry {
        return DB::transaction(function () use ($inquiry, $validated, $original, $wasConfirmed, $inquiryService) {
            $inquiry->fill([
                'booking_type' => $validated['booking_type'],
                'cottage_id' => (int) $validated['cottage_id'],
                'check_in' => $validated['check_in'],
                'check_out' => $validated['check_out'] ?? null,
                'pax' => (int) $validated['pax'],
            ]);

            // Recompute the total for the new schedule, keeping any promo
            // discount already applied so it is never silently re-awarded.
            $newTotal = $inquiryService->calculateTotal([
                'booking_type' => $inquiry->booking_type,
                'cottage_id' => $inquiry->cottage_id,
                'check_in' => $inquiry->check_in?->format('Y-m-d'),
                'check_out' => $inquiry->check_out?->format('Y-m-d'),
            ]);

            if ($newTotal !== null) {
                // Money Migration: exact string recompute over the already-exact
                // Money total, keeping the discount and the non-negative clamp
                // (same style as PricingService::applyDiscount()). Null totals
                // still skip the recompute; transaction and blocks unchanged.
                $base = Money::from($newTotal);
                $discount = Money::from($inquiry->discount_amount ?? '0.00');
                $inquiry->total_amount = Money::cmp($base, $discount) < 0 ? '0.00' : Money::sub($base, $discount);
            }

            $inquiry->save();

            $inquiry->releaseBlocks($original);

            if ($wasConfirmed) {
                $inquiry->bookBlocks();
            } else {
                $inquiry->reserveBlocks();
            }

            return $inquiry;
        });
    }
}
