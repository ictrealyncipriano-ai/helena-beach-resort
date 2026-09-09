<?php

namespace App\Services;

use App\Models\Inquiry;
use App\Models\Payment;

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
     * WP-7 durable refund lifecycle on inquiries.refund_status:
     * none → processing → completed, or → failed (retryable by
     * payments:retry-refunds and by the admin Retry button, which is the
     * existing refund endpoint). Manually-collected money never enters this
     * lifecycle (no provider payment to reverse — offline handling as before).
     */
    public const STATUS_NONE = 'none';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_FAILED = 'failed';
    public const STATUS_COMPLETED = 'completed';

    /** Attempts after which the retry command stops (needs human review). */
    public const MAX_ATTEMPTS = 5;

    /**
     * Atomically claim the refund slot and process it via PayMongo.
     *
     * @return string  self::CLAIMED | self::ALREADY_CLAIMED
     *
     * @throws \RuntimeException  When the PayMongo refund API call fails.
     */
    public function claimAndProcess(Inquiry $inquiry, PayMongoService $payMongo): string
    {
        // Manual settlements never enter the online-refund lifecycle (no
        // provider payment to reverse): the claim only guards concurrency,
        // status/attempts stay at none/0.
        $hasOnlinePayment = $inquiry->paymongo_payment_id !== null;

        $claimUpdate = ['refunded_at' => now()];

        if ($hasOnlinePayment) {
            $claimUpdate += [
                'refund_status' => self::STATUS_PROCESSING,
                'refund_attempts' => $inquiry->refund_attempts + 1,
            ];
        }

        $claimed = Inquiry::where('id', $inquiry->id)
            ->whereNull('refunded_at')
            ->update($claimUpdate);

        if ($claimed !== 1) {
            return self::ALREADY_CLAIMED;
        }

        try {
            $result = $payMongo->refund($inquiry);
        } catch (\RuntimeException $e) {
            // Roll the claim back so the caller can retry.
            // A model-level update() would skip the column: the in-memory
            // refunded_at is still null (the claim was a bulk update), so it
            // never registers as dirty. Update at the query level instead.
            // WP-7: persist online failures for the retry command.
            $failedUpdate = ['refunded_at' => null];

            if ($hasOnlinePayment) {
                $failedUpdate += [
                    'refund_status' => self::STATUS_FAILED,
                    'refund_last_error' => mb_substr($e->getMessage(), 0, 500),
                ];
            }

            Inquiry::where('id', $inquiry->id)->update($failedUpdate);

            throw $e;
        }

        // WP-1 dual-write: record the refund in the ledger and mark the
        // settled paid rows as refunded (current policy is full-only refunds
        // of whatever was collected). Idempotent: a re-run on an
        // already-refunded booking is rejected by the claim guard above, and
        // the refund row itself deduplicates on provider_refund_id.
        $refundId = is_array($result) ? ($result['id'] ?? null) : null;
        $refundQuery = Payment::where('inquiry_id', $inquiry->id)
            ->where('type', '!=', Payment::TYPE_REFUND)
            ->where('status', Payment::STATUS_PAID);

        if ($refundId !== null) {
            Payment::firstOrCreate(
                ['provider_refund_id' => $refundId],
                [
                    'inquiry_id' => $inquiry->id,
                    'provider' => Payment::PROVIDER_PAYMONGO,
                    'provider_payment_id' => null,
                    'provider_refund_id' => $refundId,
                    'method' => $inquiry->payment_method,
                    'type' => Payment::TYPE_REFUND,
                    'amount' => $inquiry->refundableAmount(),
                    'currency' => 'PHP',
                    'status' => Payment::STATUS_REFUNDED,
                    'refunded_at' => now(),
                    'metadata' => ['refunded_payment_id' => $inquiry->paymongo_payment_id],
                ]
            );
        } else {
            $exists = Payment::where('inquiry_id', $inquiry->id)
                ->where('type', Payment::TYPE_REFUND)
                ->where('amount', $inquiry->refundableAmount())
                ->exists();
            if (! $exists) {
                Payment::create([
                    'inquiry_id' => $inquiry->id,
                    'provider' => Payment::PROVIDER_PAYMONGO,
                    'provider_payment_id' => null,
                    'method' => $inquiry->payment_method,
                    'type' => Payment::TYPE_REFUND,
                    'amount' => $inquiry->refundableAmount(),
                    'currency' => 'PHP',
                    'status' => Payment::STATUS_REFUNDED,
                    'refunded_at' => now(),
                    'metadata' => ['refunded_payment_id' => $inquiry->paymongo_payment_id],
                ]);
            }
        }

        $refundQuery->update(['status' => Payment::STATUS_REFUNDED, 'refunded_at' => now()]);

        Inquiry::where('id', $inquiry->id)->update([
            'refund_status' => self::STATUS_COMPLETED,
            'refund_last_error' => null,
        ]);

        return self::CLAIMED;
    }
}
