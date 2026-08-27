<?php

namespace App\Rules;

use App\Models\Cottage;
use App\Models\Inquiry;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates that a cottage is not blocked on any date in the requested range.
 *
 * Shared by the public booking and inquiry forms. When $skipOwnDuplicate is
 * true (booking flow) a guest's own pending inquiry from the last 10 minutes
 * is allowed to hold the dates so a double-click / network retry re-submitting
 * the exact same request is not rejected here — the store() idempotency guard
 * reuses that earlier booking instead. Any other block (a different guest, an
 * older booking, a manual admin block) still fails validation.
 */
class CottageAvailability implements ValidationRule
{
    public function __construct(
        private readonly mixed $cottageId,
        private readonly mixed $email,
        private readonly mixed $bookingType,
        private readonly mixed $checkOut,
        private readonly bool $skipOwnDuplicate = false,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $this->cottageId || ! $value) {
            return;
        }

        if ($this->skipOwnDuplicate) {
            $ownDuplicate = Inquiry::query()
                ->where('email', $this->email)
                ->where('status', Inquiry::STATUS_PENDING)
                ->where('booking_type', $this->bookingType)
                ->where('cottage_id', $this->cottageId)
                ->whereDate('check_in', $value)
                ->when(
                    $this->checkOut,
                    fn ($q, $checkOut) => $q->whereDate('check_out', $checkOut),
                    fn ($q) => $q->whereNull('check_out')
                )
                ->where('created_at', '>=', now()->subMinutes(10))
                ->first();

            // Only skip when this session actually created the pending
            // booking; otherwise a spoofed email must not let the requester
            // hold (or absorb) another person's dates.
            $created = session('booking_created_inquiries', []);
            if ($ownDuplicate && is_array($created) && in_array($ownDuplicate->id, $created, true)) {
                return;
            }
        }

        $cottage = Cottage::find($this->cottageId);
        if (! $cottage) {
            return;
        }

        $checkOut = $this->checkOut ?? $value;

        $blockedDates = $cottage->dateBlocks()
            ->whereBetween('date', [$value, $checkOut])
            ->pluck('date')
            ->map(fn ($d) => $d->format('M d, Y'))
            ->implode(', ');

        if ($blockedDates) {
            $fail("The cottage is not available on: {$blockedDates}.");
        }
    }
}