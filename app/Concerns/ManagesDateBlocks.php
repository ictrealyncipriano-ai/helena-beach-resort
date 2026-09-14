<?php

namespace App\Concerns;

use App\Exceptions\BookingConflictException;
use App\Models\CottageDateBlock;
use App\Support\StayDates;

/**
 * Reservation-block management for an inquiry. Encapsulates how an inquiry
 * reserves, books, and releases the (cottage_id, date) holds that make its
 * dates unavailable to other guests.
 *
 * Requires the consuming model to define: $this->cottage_id, $this->check_in,
 * $this->check_out, $this->id, and $this->reference_code.
 */
trait ManagesDateBlocks
{
    /**
     * Create CottageDateBlock rows for the inquiry's date range so the
     * cottage is held (reserved) as soon as the inquiry is submitted.
     *
     * Throws a BookingConflictException when a date is already held by a
     * different inquiry or a manual admin block. A single SELECT detects any
     * conflict across the whole range before a single INSERT writes every
     * night, so no partial hold can ever be left behind on a mid-range
     * conflict. The (cottage_id, date) unique index then arbitrates the
     * concurrent-submission race at the database level: the write uses
     * INSERT ... ON CONFLICT DO NOTHING, so a racing submission can never
     * overwrite the first writer's block.
     */
    public function reserveBlocks(): void
    {
        $dates = $this->dateRange();
        if ($dates === []) {
            return;
        }

        $pendingReason = $this->reasonLabel('Pending');

        $conflict = CottageDateBlock::where('cottage_id', $this->cottage_id)
            ->whereIn('date', $dates)
            ->where(function ($q) use ($pendingReason) {
                $q->whereNull('reason')->orWhere('reason', '!=', $pendingReason);
            })
            ->orderBy('date')
            ->first();

        if ($conflict) {
            throw new BookingConflictException(
                "The cottage is already reserved on {$conflict->date} ({$conflict->reason})."
            );
        }

        $this->insertBlocks(
            $this->blockRows($pendingReason, $dates),
            $dates,
            'These dates are no longer available.'
        );
    }

    /**
     * Promote existing pending blocks to booked (creating any missing
     * rows, e.g. for legacy inquiries that predate reservation-on-submit).
     *
     * Throws a BookingConflictException instead of overwriting a block that
     * belongs to a different inquiry or a manual admin block.
     */
    public function bookBlocks(): void
    {
        $dates = $this->dateRange();
        if ($dates === []) {
            return;
        }

        $allowedReasons = [
            $this->reasonLabel('Pending'),
            $this->reasonLabel('Booked'),
        ];

        $conflict = CottageDateBlock::where('cottage_id', $this->cottage_id)
            ->whereIn('date', $dates)
            ->where(function ($q) use ($allowedReasons) {
                $q->whereNull('reason')->orWhereNotIn('reason', $allowedReasons);
            })
            ->orderBy('date')
            ->first();

        if ($conflict) {
            throw new BookingConflictException(
                "Cottage is already reserved on {$conflict->date} by another booking ({$conflict->reason})."
            );
        }

        $this->insertBlocks(
            $this->blockRows($this->reasonLabel('Booked'), $dates),
            $dates,
            'These dates are no longer available.',
            $this->reasonLabel('Booked')
        );
    }

    /**
     * Write every block row in one atomic INSERT, relying on the
     * (cottage_id, date) unique index to arbitrate a concurrent submission.
     *
     * INSERT ... ON CONFLICT DO NOTHING (insertOrIgnore) is used instead of
     * an upsert so a racing writer can never OVERWRITE the first writer's
     * block: a conflicted row is silently skipped and the other writer's
     * inquiry_id/reason stay intact. When fewer rows are inserted than
     * expected, the surviving rows are checked — a block belonging to a
     * different inquiry or to a manual admin block (inquiry_id null) is a
     * real conflict and triggers a BookingConflictException (the caller's
     * DB::transaction then rolls back). Blocks already held by THIS inquiry
     * (e.g. re-confirming a stay that still carries its own pending hold)
     * are not conflicts; when $promoteReason is given they are updated in
     * place so confirmation still promotes Pending -> Booked.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @param  string[]  $dates
     */
    private function insertBlocks(array $rows, array $dates, string $message, ?string $promoteReason = null): void
    {
        $inserted = CottageDateBlock::insertOrIgnore($rows);

        if ($inserted === count($rows)) {
            return;
        }

        $foreignConflict = CottageDateBlock::where('cottage_id', $this->cottage_id)
            ->whereIn('date', $dates)
            ->where(function ($q) {
                if ($this->id) {
                    $q->whereNull('inquiry_id')
                        ->orWhere('inquiry_id', '!=', $this->id);
                }
            })
            ->exists();

        if ($foreignConflict) {
            throw new BookingConflictException($message);
        }

        // Every skipped row belongs to this inquiry (e.g. a pending hold being
        // confirmed). Promote those self-owned blocks to the target reason —
        // only this inquiry's rows are touched, so the write is race-safe.
        if ($promoteReason !== null && $this->id) {
            CottageDateBlock::where('cottage_id', $this->cottage_id)
                ->whereIn('date', $dates)
                ->where('inquiry_id', $this->id)
                ->update(['reason' => $promoteReason, 'updated_at' => now()]);
        }
    }

    /**
     * Remove all blocks held by this inquiry (pending or booked).
     *
     * @param  array{cottage_id: ?int, check_in: ?string, check_out: ?string, booking_type?: ?string}|null  $original
     */
    public function releaseBlocks(?array $original = null): void
    {
        if ($original !== null) {
            $cottageId = $original['cottage_id'] ?? $this->cottage_id;
            $checkIn = $original['check_in'] ?? null;
            $checkOut = $original['check_out'] ?? null;
            // The model already carries the NEW booking_type after fill/update,
            // so an explicit original type is required to release a switched
            // stay correctly. Fall back to overnight (the superset) rather
            // than the new type so a day_tour switch never orphans the old
            // second night.
            $bookingType = $original['booking_type'] ?? \App\Models\Inquiry::TYPE_OVERNIGHT;
        } else {
            $cottageId = $this->cottage_id;
            $checkIn = $this->check_in;
            $checkOut = $this->check_out ?? null;
            $bookingType = $this->booking_type ?? null;
        }

        if (! $cottageId || ! $checkIn) {
            return;
        }

        // Same exclusive range as reserve/book: [check_in, check_out) for
        // overnight so a release never deletes the check-out day that the
        // next back-to-back booking already owns.
        $dates = StayDates::blockedDates($checkIn, $checkOut, $bookingType);

        if ($dates === []) {
            return;
        }

        $query = CottageDateBlock::where('cottage_id', $cottageId)
            ->whereIn('date', $dates);

        // Blocks written before the inquiry_id column existed still carry the
        // reference-code reason, so match either the FK or the legacy reason.
        if ($this->id) {
            $query->where(function ($q) {
                $q->where('inquiry_id', $this->id)
                    ->orWhereIn('reason', [
                        $this->reasonLabel('Pending'),
                        $this->reasonLabel('Booked'),
                    ]);
            });
        } else {
            $query->whereIn('reason', [
                $this->reasonLabel('Pending'),
                $this->reasonLabel('Booked'),
            ]);
        }

        $query->delete();
    }

    /**
     * Build the insert payload for one stay. `inquiry_id` links every block
     * to the inquiry that holds it so downstream queries never have to parse
     * the human-readable reason string.
     *
     * @param  string[]  $dates
     * @return array<int, array<string, mixed>>
     */
    private function blockRows(string $reason, array $dates): array
    {
        $now = now();

        return array_map(fn (string $date) => [
            'cottage_id' => $this->cottage_id,
            'date' => $date,
            'reason' => $reason,
            'inquiry_id' => $this->id,
            'created_at' => $now,
            'updated_at' => $now,
        ], $dates);
    }

    private function reasonLabel(string $type): string
    {
        return "{$type}: {$this->reference_code}";
    }

    /**
     * Every calendar date blocked by the stay: [check_in, check_out) for
     * overnight (check-out stays available for the next booking), or
     * [check_in] for a day tour.
     *
     * @return string[]
     */
    private function dateRange(): array
    {
        if (! $this->cottage_id || ! $this->check_in) {
            return [];
        }

        return StayDates::blockedDates(
            $this->check_in,
            $this->check_out ?? null,
            $this->booking_type ?? null
        );
    }
}
