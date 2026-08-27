<?php

namespace App\Services;

use App\Models\Inquiry;

/**
 * Atomically claim and process a PayMongo refund for an inquiry.
 *
 * The TOCTOU guard (claim-then-refund) prevents two concurrent requests from
 * double-refunding the same booking: the first UPDATE … WHERE refunded_at
 * IS NULL sets the column and returns 1 affected row; a second concurrent
 * caller sees 0 and is rejected before ever hitting the PayMongo API.
 *
 * If the PayMongo call fails, the claim is rolled back so the caller (or an
 * admin) can retry after fixing the underlying issue.
 */
class RefundService
{
    public const CLAIMED = 'claimed';
    public const ALREADY_CLAIMED = 'already_claimed';

    /**
     * Atomically claim the refund slot and process it via PayMongo.
     *
     * @return string  self::CLAIMED | self::ALREADY_CLAIMED
     *
     * @throws \RuntimeException  When the PayMongo refund API call fails.
     */
    public function claimAndProcess(Inquiry $inquiry, PayMongoService $payMongo): string
    {
        $claimed = Inquiry::where('id', $inquiry->id)
            ->whereNull('refunded_at')
            ->update(['refunded_at' => now()]);

        if ($claimed !== 1) {
            return self::ALREADY_CLAIMED;
        }

        try {
            $payMongo->refund($inquiry);
        } catch (\RuntimeException $e) {
            // Roll the claim back so the caller can retry.
            // A model-level update() would skip the column: the in-memory
            // refunded_at is still null (the claim was a bulk update), so it
            // never registers as dirty. Update at the query level instead.
            Inquiry::where('id', $inquiry->id)->update(['refunded_at' => null]);

            throw $e;
        }

        return self::CLAIMED;
    }
}
