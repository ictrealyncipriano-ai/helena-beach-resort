<?php

namespace App\Services;

use App\Http\Controllers\Admin\DashboardController;
use App\Mail\BookingCancelled;
use App\Mail\ManualRefundRequired;
use App\Mail\RefundReceived;
use App\Models\Inquiry;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Guest-facing booking cancellation: refund whatever was collected,
 * finalize the booking row, and notify guest + owner.
 *
 * Message strings and state transitions are byte-identical to the legacy
 * BookingPortalController implementation this was extracted from.
 */
class BookingCancellationService
{
    public function __construct(private RefundService $refundService)
    {
    }

    /**
     * P1.1: refunds the tiered CancellationPolicy quote (share of collected),
     * not always the full amount. Manual money still flags offline handling
     * with the quoted share attached.
     *
     * @return array{refunded: bool, refundFailed: bool, refundAlreadyProcessed: bool, manualRefundRequired: bool, wasConfirmed: bool, quote: array{pct: int, hours_before: int, collected: string, refund_amount: string, forfeit_amount: string}}
     */
    public function processRefund(Inquiry $inquiry, PayMongoService $payMongo): array
    {
        $refunded = false;
        $refundFailed = false;
        $refundAlreadyProcessed = false;
        $manualRefundRequired = false;
        $quote = CancellationPolicy::quote($inquiry);

        if ($inquiry->hasPayments() && (float) $quote['refund_amount'] > 0) {
            if ($inquiry->paymongo_payment_id) {
                try {
                    $refunded = $this->refundService->claimAndProcess($inquiry, $payMongo, $quote['refund_amount']) === RefundService::CLAIMED;
                } catch (\RuntimeException $e) {
                    Log::warning('Auto-refund failed on guest cancellation', [
                        'inquiry_id' => $inquiry->id,
                        'error' => $e->getMessage(),
                        'quote_pct' => $quote['pct'],
                        'quote_refund' => $quote['refund_amount'],
                    ]);
                    $refundFailed = true;
                }

                if (! $refunded && ! $refundFailed) {
                    // Another request (or the admin) already processed the refund.
                    $refundAlreadyProcessed = true;
                }
            } else {
                // Money collected manually (cash / bank transfer) has no PayMongo
                // payment to reverse. Never silently retain it: flag it for an
                // offline refund by the resort.
                $manualRefundRequired = true;

                Log::warning('Guest cancellation with manually-collected payment requires offline refund', [
                    'inquiry_id' => $inquiry->id,
                    'reference_code' => $inquiry->reference_code,
                    'collected_amount' => $inquiry->collectedAmount(),
                    'quote_pct' => $quote['pct'],
                    'quote_refund' => $quote['refund_amount'],
                ]);
            }
        }

        return [
            'refunded' => $refunded,
            'refundFailed' => $refundFailed,
            'refundAlreadyProcessed' => $refundAlreadyProcessed,
            'manualRefundRequired' => $manualRefundRequired,
            'wasConfirmed' => $inquiry->status === Inquiry::STATUS_CONFIRMED,
            'quote' => $quote,
        ];
    }

    /**
     * Cancel the booking and reverse the recorded stay. Reloaded after the
     * refund claim so refunded_at/refund_amount reflect whatever state the
     * database holds (a concurrent writer may have set them).
     *
     * P1.1: when a tiered refund just succeeded, persist the quoted share
     * (not the full collected amount).
     *
     * One DB::transaction covers the status/refund update + block release +
     * stay reversal, so a failure at any point rolls the whole cancellation
     * back. The block release uses an explicit snapshot taken from the
     * locked row — never the caller's possibly-mutated attributes (e.g. an
     * overnight→day-tour type switch held in memory) — with the exclusive
     * [check_in, check_out) semantics from b37a7e4.
     *
     * Already-cancelled rows are a no-op (double cancel releases blocks and
     * decrements the stay exactly once). Dashboard cache invalidation stays
     * after commit.
     *
     * NOTE (scope): the sibling sequences in Admin\InquiryController::refund
     * (status/refund fields + releaseBlocks + reverseStay), destroy
     * (releaseBlocks + delete), and the CancelsBookings::cancelBooking trait
     * duplicate these steps on purpose and are intentionally NOT unified
     * here — different guards/contracts per path. Keep them in sync by hand.
     *
     * @param  array{refunded: bool, wasConfirmed: bool, quote?: array{refund_amount: string}}  $refundState
     */
    public function finalizeCancellation(Inquiry $inquiry, bool $wasConfirmed, ?array $refundState = null): void
    {
        DB::transaction(function () use ($inquiry, $wasConfirmed, $refundState) {
            $locked = Inquiry::whereKey($inquiry->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === Inquiry::STATUS_CANCELLED) {
                $inquiry->setRawAttributes($locked->getAttributes(), true);

                return;
            }

            $quotedRefund = $refundState['quote']['refund_amount'] ?? null;
            // The P1.1 quoted share is authoritative on a fresh success. On any
            // later retry path (refundFailed / refundAlreadyProcessed) a
            // persisted refund_amount must win over refundableAmount() so the
            // quoted share is never overwritten with the full collected amount.
            $refundAmount = ($locked->refunded_at && ($refundState['refunded'] ?? false) && $quotedRefund !== null)
                ? $quotedRefund
                : ($locked->refund_amount ?? ($locked->refunded_at ? $locked->refundableAmount() : $locked->refund_amount));

            $original = [
                'cottage_id' => $locked->cottage_id,
                'check_in' => $locked->check_in?->format('Y-m-d'),
                'check_out' => $locked->check_out?->format('Y-m-d'),
                'booking_type' => $locked->booking_type,
            ];

            $locked->update([
                'status' => Inquiry::STATUS_CANCELLED,
                'refunded_at' => $locked->refunded_at,
                'refund_amount' => $refundAmount,
            ]);
            $this->releaseInquiryBlocks($locked, $original);

            // Only decrement a recorded stay when this was a confirmed booking
            // (markConfirmed() increments it); never let a cancel push a pending
            // booking's count below zero.
            if ($wasConfirmed) {
                $locked->reverseStay();
            }

            $inquiry->setRawAttributes($locked->refresh()->getAttributes(), true);
        });

        // Guest cancellations change the same dashboard aggregates as admin
        // ones (pending/confirmed counts, revenue), so drop the cached stats.
        DashboardController::forgetCache();
    }

    /**
     * Single seam for the block release inside finalizeCancellation's
     * transaction. Exists so tests can force a release failure (subclass
     * override) and assert the whole cancellation rolls back.
     *
     * @param  array{cottage_id: ?int, check_in: ?string, check_out: ?string, booking_type?: ?string}  $original
     */
    protected function releaseInquiryBlocks(Inquiry $locked, array $original): void
    {
        $locked->releaseBlocks($original);
    }

    /**
     * @param  array{refunded: bool, manualRefundRequired: bool}  $refundState
     */
    public function sendGuestCancellationEmails(Inquiry $inquiry, array $refundState): void
    {
        try {
            Mail::to($inquiry->email)->queue(new BookingCancelled($inquiry));

            if ($refundState['refunded']) {
                Mail::to($inquiry->email)->queue(new RefundReceived($inquiry->fresh()));
            }

            $ownerEmail = SiteSetting::getValue('contact_email');
            if ($ownerEmail) {
                Mail::to($ownerEmail)->queue(new BookingCancelled($inquiry));

                if ($refundState['manualRefundRequired']) {
                    Mail::to($ownerEmail)->queue(new ManualRefundRequired($inquiry->fresh()));
                }
            }
        } catch (\Exception $e) {
            Log::warning('Failed to send cancellation notification', [
                'inquiry_id' => $inquiry->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @param  array{refundFailed: bool, refundAlreadyProcessed: bool, manualRefundRequired: bool}  $refundState
     */
    public function cancellationFlashType(array $refundState): string
    {
        return ($refundState['refundFailed'] || $refundState['refundAlreadyProcessed'] || $refundState['manualRefundRequired'])
            ? 'warning'
            : 'success';
    }

    /**
     * @param  array{refunded: bool, refundFailed: bool, refundAlreadyProcessed: bool, manualRefundRequired: bool, quote?: array{pct: int, refund_amount: string, forfeit_amount: string}}  $refundState
     */
    public function cancellationFlashMessage(Inquiry $inquiry, array $refundState): string
    {
        if ($refundState['refundFailed']) {
            return 'Your booking has been cancelled, but the refund could not be processed automatically. Please contact the resort to complete your refund.';
        }

        if ($refundState['refunded']) {
            $quote = $refundState['quote'] ?? null;

            if ($quote && (int) $quote['pct'] < 100) {
                return 'Your booking has been cancelled and ₱'.$quote['refund_amount'].' has been refunded per the cancellation policy ('.$quote['pct'].'% refundable).';
            }

            return 'Your booking has been cancelled and your payment has been refunded.';
        }

        if ($refundState['manualRefundRequired']) {
            $quote = $refundState['quote'] ?? null;
            $due = $quote ? $quote['refund_amount'] : $inquiry->collectedAmount();

            return 'Your booking has been cancelled. Your refundable amount of ₱'.$due.' will be refunded directly by the resort.';
        }

        if ($refundState['refundAlreadyProcessed']) {
            return 'Your booking has been cancelled. The refund was already processed.';
        }

        return 'Your booking has been cancelled.';
    }
}
