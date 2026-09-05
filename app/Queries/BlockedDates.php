<?php

namespace App\Queries;

use App\Models\CottageDateBlock;
use Illuminate\Support\Collection;

/**
 * Single source for "which dates are blocked per cottage" lookups.
 *
 * Replaces the three hand-rolled copies in BookingController::create,
 * InquiryController::create, and BookingPortalController::modifyForm.
 * Always returns cottage_id => [Y-m-d, ...].
 */
class BlockedDates
{
    /**
     * @param  Collection<int, int>|array<int, int>  $cottageIds
     * @return Collection<int, Collection<int, string>>
     */
    public static function byCottage(Collection|array $cottageIds, ?int $excludeInquiryId = null, ?int $days = 180): Collection
    {
        $ids = $cottageIds instanceof Collection ? $cottageIds->values() : collect($cottageIds)->values();
        if ($ids->isEmpty()) {
            return collect();
        }

        $query = CottageDateBlock::whereIn('cottage_id', $ids)
            ->select('cottage_id', 'date', 'reason', 'inquiry_id');

        if ($days === null) {
            $query->future();
        } else {
            $query->whereBetween('date', [now()->startOfDay(), now()->addDays($days)->endOfDay()]);
        }

        $blocks = $query->get();

        if ($excludeInquiryId !== null) {
            // FK-primary exclusion with legacy reason-string fallback for
            // pre-backfill rows (NULL inquiry_id carrying our reference code
            // cannot be identified without the code, so callers needing that
            // case should filter further with the inquiry's reference_code).
            $blocks = $blocks->reject(fn (CottageDateBlock $b) => $b->inquiry_id === $excludeInquiryId);
        }

        return $blocks
            ->groupBy('cottage_id')
            ->map(fn ($group) => $group->pluck('date')
                ->map(fn ($date) => $date->format('Y-m-d'))
                ->values());
    }

    /**
     * Variant for the modify form: excludes the given inquiry's own holds,
     * including legacy NULL-FK rows whose reason carries its reference code.
     *
     * @return Collection<int, Collection<int, string>>
     */
    public static function byCottageExcludingInquiry(Collection|array $cottageIds, int $inquiryId, string $referenceCode): Collection
    {
        $ids = $cottageIds instanceof Collection ? $cottageIds->values() : collect($cottageIds)->values();
        if ($ids->isEmpty()) {
            return collect();
        }

        return CottageDateBlock::whereIn('cottage_id', $ids)
            ->future()
            ->where(function ($q) use ($inquiryId) {
                $q->where('inquiry_id', '!=', $inquiryId)
                    ->orWhereNull('inquiry_id');
            })
            ->select('cottage_id', 'date', 'reason', 'inquiry_id')
            ->get()
            ->reject(fn (CottageDateBlock $b) => $b->inquiry_id === $inquiryId
                || ($b->inquiry_id === null && str_contains((string) $b->reason, $referenceCode)))
            ->groupBy('cottage_id')
            ->map(fn ($group) => $group->pluck('date')
                ->map(fn ($date) => $date->format('Y-m-d'))
                ->values());
    }
}
