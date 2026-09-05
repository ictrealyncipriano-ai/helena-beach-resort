<?php

namespace App\Services;

use App\Http\Controllers\Admin\DashboardController;
use App\Mail\BookingCancelled;
use App\Mail\ManualRefundRequired;
use App\Mail\RefundReceived;
use App\Models\Inquiry;
use App\Models\SiteSetting;
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
     * @return array{refunded: bool, refundFailed: bool, refundAlreadyProcessed: bool, manualRefundRequired: bool, wasConfirmed: bool}
     */
    public function processRefund(Inquiry $inquiry, PayMongoService $payMongo): array
    {
        $refunded = false;
        $refundFailed = false;
        $refundAlreadyProcessed = false;
        $manualRefundRequired = false;

        if ($inquiry->hasPayments()) {
            if ($inquiry->paymongo_payment_id) {
                try {
                    $refunded = $this->refundService->claimAndProcess($inquiry, $payMongo) === RefundService::CLAIMED;
                } catch (\RuntimeException $e) {
                    Log::warning('Auto-refund failed on guest cancellation', [
                        'inquiry_id' => $inquiry->id,
                        'error' => $e->getMessage(),
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
                ]);
            }
        }

        return [
            'refunded' => $refunded,
            'refundFailed' => $refundFailed,
            'refundAlreadyProcessed' => $refundAlreadyProcessed,
            'manualRefundRequired' => $manualRefundRequired,
            'wasConfirmed' => $inquiry->status === Inquiry::STATUS_CONFIRMED,
        ];
    }

    /**
     * Cancel the booking and reverse the recorded stay. Reloaded after the
     * refund claim so refunded_at/refund_amount reflect whatever state the
     * database holds (a concurrent writer may have set them).
     *
     * @param  array{refunded: bool, wasConfirmed: bool}  $refundState
     */
    public function finalizeCancellation(Inquiry $inquiry, bool $wasConfirmed): void
    {
        $inquiry->refresh();

        $inquiry->update([
            'status' => Inquiry::STATUS_CANCELLED,
            'refunded_at' => $inquiry->refunded_at,
            'refund_amount' => $inquiry->refunded_at ? $inquiry->refundableAmount() : $inquiry->refund_amount,
        ]);
        $inquiry->releaseBlocks();

        // Only decrement a recorded stay when this was a confirmed booking
        // (markConfirmed() increments it); never let a cancel push a pending
        // booking's count below zero.
        if ($wasConfirmed) {
            $inquiry->reverseStay();
        }

        // Guest cancellations change the same dashboard aggregates as admin
        // ones (pending/confirmed counts, revenue), so drop the cached stats.
        DashboardController::forgetCache();
    }

    /**
     * @param  array{refunded: bool, manualRefundRequired: bool}  $refundState
     */
    public function sendGuestCancellationEmails(Inquiry $inquiry, array $refundState): void
    {
        try {
            Mail::to($inquiry->email)->send(new BookingCancelled($inquiry));

            if ($refundState['refunded']) {
                Mail::to($inquiry->email)->send(new RefundReceived($inquiry->fresh()));
            }

            $ownerEmail = SiteSetting::getValue('contact_email');
            if ($ownerEmail) {
                Mail::to($ownerEmail)->send(new BookingCancelled($inquiry));

                if ($refundState['manualRefundRequired']) {
                    Mail::to($ownerEmail)->send(new ManualRefundRequired($inquiry->fresh()));
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
     * @param  array{refunded: bool, refundFailed: bool, refundAlreadyProcessed: bool, manualRefundRequired: bool}  $refundState
     */
    public function cancellationFlashMessage(Inquiry $inquiry, array $refundState): string
    {
        if ($refundState['refundFailed']) {
            return 'Your booking has been cancelled, but the refund could not be processed automatically. Please contact the resort to complete your refund.';
        }

        if ($refundState['refunded']) {
            return 'Your booking has been cancelled and your payment has been refunded.';
        }

        if ($refundState['manualRefundRequired']) {
            return 'Your booking has been cancelled. Your payment of ₱'.$inquiry->collectedAmount().' will be refunded directly by the resort.';
        }

        if ($refundState['refundAlreadyProcessed']) {
            return 'Your booking has been cancelled. The refund was already processed.';
        }

        return 'Your booking has been cancelled.';
    }
}
