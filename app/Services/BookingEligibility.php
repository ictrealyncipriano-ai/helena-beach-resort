<?php

namespace App\Services;

use App\Models\Inquiry;
use App\Models\Testimonial;

/**
 * Single source of truth for guest portal eligibility gates.
 *
 * Replaces the duplicated temporal + payment checks previously spread across
 * BookingPortalController (canModify/canCancel/cannot*Reason/hasPayments).
 * Results are memoized per instance (one controller instance per request)
 * so show()/modifyForm()/modify() never recompute the same gate twice.
 * Message strings are byte-identical to the legacy controller versions.
 */
class BookingEligibility
{
    public const CUTOFF_HOURS = 24;

    /** @var array<string, mixed> */
    private array $memo = [];

    public function hasPayments(Inquiry $inquiry): bool
    {
        return $inquiry->hasPayments();
    }

    public function canModify(Inquiry $inquiry): bool
    {
        return $this->remember("modify.{$inquiry->id}", function () use ($inquiry) {
            if (! in_array($inquiry->status, [Inquiry::STATUS_PENDING, Inquiry::STATUS_CONFIRMED], true)) {
                return false;
            }

            if (! $inquiry->check_in || $inquiry->check_in->isPast()) {
                return false;
            }

            if (now()->diffInHours($inquiry->check_in) < self::CUTOFF_HOURS) {
                return false;
            }

            if ($this->hasPayments($inquiry)) {
                return false;
            }

            return true;
        });
    }

    public function cannotModifyReason(Inquiry $inquiry): ?string
    {
        if (! in_array($inquiry->status, [Inquiry::STATUS_PENDING, Inquiry::STATUS_CONFIRMED], true)) {
            return 'This booking can no longer be modified.';
        }

        if ($this->hasPayments($inquiry)) {
            return 'To change the dates or cottage of a paid booking, please contact the resort.';
        }

        if (! $inquiry->check_in || $inquiry->check_in->isPast()) {
            return 'This booking can no longer be modified.';
        }

        if (now()->diffInHours($inquiry->check_in) < self::CUTOFF_HOURS) {
            return 'Modification is no longer available. This booking can be changed until 24 hours before check-in (cutoff: '
                .$inquiry->check_in->format('M d, Y').').';
        }

        return null;
    }

    public function canCancel(Inquiry $inquiry): bool
    {
        return $this->remember("cancel.{$inquiry->id}", function () use ($inquiry) {
            if (! in_array($inquiry->status, [Inquiry::STATUS_PENDING, Inquiry::STATUS_CONFIRMED], true)) {
                return false;
            }

            if (! $inquiry->check_in || $inquiry->check_in->isPast()) {
                return false;
            }

            if (now()->diffInHours($inquiry->check_in) < self::CUTOFF_HOURS) {
                return false;
            }

            return true;
        });
    }

    public function cannotCancelReason(Inquiry $inquiry): ?string
    {
        if (! in_array($inquiry->status, [Inquiry::STATUS_PENDING, Inquiry::STATUS_CONFIRMED], true)) {
            return 'This booking can no longer be cancelled.';
        }

        if (! $inquiry->check_in || $inquiry->check_in->isPast()) {
            return 'This booking can no longer be cancelled.';
        }

        if (now()->diffInHours($inquiry->check_in) < self::CUTOFF_HOURS) {
            return 'Cancellation is no longer available. This booking can be cancelled until 24 hours before check-in (cutoff: '
                .$inquiry->check_in->format('M d, Y').').';
        }

        return null;
    }

    public function canReview(Inquiry $inquiry): bool
    {
        if ($inquiry->status !== Inquiry::STATUS_CONFIRMED) {
            return false;
        }

        $endDate = $inquiry->booking_type === Inquiry::TYPE_DAY_TOUR ? $inquiry->check_in : $inquiry->check_out;
        if (! $endDate || $endDate->isFuture()) {
            return false;
        }

        return ! Testimonial::where('inquiry_id', $inquiry->id)->exists();
    }

    public function canSubmitPaymentProof(Inquiry $inquiry): bool
    {
        if ($inquiry->status !== Inquiry::STATUS_CONFIRMED || $inquiry->isPaid()) {
            return false;
        }

        return ! in_array($inquiry->payment_proof_status, [Inquiry::PROOF_PENDING, Inquiry::PROOF_APPROVED], true);
    }

    private function remember(string $key, callable $callback): bool
    {
        if (! array_key_exists($key, $this->memo)) {
            $this->memo[$key] = $callback();
        }

        return $this->memo[$key];
    }
}
