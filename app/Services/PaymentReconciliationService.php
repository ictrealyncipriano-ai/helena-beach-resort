<?php

namespace App\Services;

use App\Http\Controllers\Admin\DashboardController;
use App\Mail\PaymentReceived;
use App\Models\Inquiry;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * WP-2: single reconciliation layer for PayMongo money.
 *
 * PayMongo → PaymentReconciliationService → payments ledger → inquiries summary.
 *
 * Three entry points reconcile by the strongest available identifier —
 * checkout id, payment id, or inquiry — sharing one locked credit routine
 * (`creditFromCheckoutResource`) that is also used by the webhook, so there
 * is exactly one place that turns remote money into local rows.
 *
 * Conservative by design: ambiguous states are reported, never mutated.
 */
class PaymentReconciliationService
{
    /**
     * Hours after which a session-backed pending checkout is re-checked
     * against PayMongo and cleared when unpaid.
     */
    public const CHECKOUT_PENDING_TTL_HOURS = 3;

    /**
     * Hours after which a session-less legacy pending equal to the full
     * total is cleared without a remote check (baseline-neutral: the
     * webhook would verify against the same total afterwards).
     */
    public const LEGACY_PENDING_TTL_HOURS = 24;

    public function __construct(
        private PayMongoService $payMongo,
        private ActivityLogger $logger,
    ) {
    }

    /**
     * Reconcile from a hosted checkout session id.
     *
     * @return array{outcome: string, inquiry_id?: int|null}
     */
    public function reconcileByCheckoutId(string $checkoutId): array
    {
        try {
            $session = $this->payMongo->retrieveCheckoutSession($checkoutId);
        } catch (\RuntimeException $e) {
            return ['outcome' => 'error', 'inquiry_id' => null, 'error' => $e->getMessage()];
        }

        $sessionId = $session['id'] ?? $checkoutId;
        $attributes = $session['attributes'] ?? [];
        $reference = $attributes['reference_number'] ?? $attributes['external_reference_number'] ?? null;

        $inquiry = $reference
            ? Inquiry::where('reference_code', $reference)->first()
            : null;

        if (! $inquiry) {
            Log::warning('Payment reconcile: no inquiry for checkout session', [
                'session_id' => $sessionId,
                'reference_number' => $reference,
            ]);

            return ['outcome' => 'unmatched', 'inquiry_id' => null];
        }

        $paid = ! empty($attributes['paid_at'])
            || ($attributes['payments'][0]['attributes']['status'] ?? null) === 'paid';

        if (! $paid) {
            return ['outcome' => 'not_paid', 'inquiry_id' => $inquiry->id];
        }

        $earlyExit = $this->creditFromCheckoutResource($inquiry, $attributes, $sessionId);

        if ($earlyExit === null) {
            $this->afterRecorded($inquiry->refresh());

            return ['outcome' => 'recorded', 'inquiry_id' => $inquiry->id];
        }

        return ['outcome' => $this->earlyExitOutcome($earlyExit), 'inquiry_id' => $inquiry->id] + $earlyExit;
    }

    /**
     * Reconcile from a PayMongo payment id.
     *
     * Never creates money from a bare payment id: when no local ledger row
     * exists the outcome is `unmatched` and the discrepancy is logged for a
     * human (or WP-6 late-payment flow) to resolve.
     *
     * @return array{outcome: string, inquiry_id?: int|null}
     */
    public function reconcileByPaymentId(string $paymentId): array
    {
        $local = Payment::where('provider_payment_id', $paymentId)->first();

        try {
            $remote = $this->payMongo->retrievePayment($paymentId);
        } catch (\RuntimeException $e) {
            return [
                'outcome' => 'error',
                'inquiry_id' => $local?->inquiry_id,
                'error' => $e->getMessage(),
            ];
        }

        $remoteAttrs = $remote['attributes'] ?? [];
        $remoteStatus = $remoteAttrs['status'] ?? null;
        $remoteCentavos = isset($remoteAttrs['amount']) ? (int) $remoteAttrs['amount'] : null;

        if (! $local) {
            Log::warning('Payment reconcile: remote payment has no local ledger row', [
                'payment_id' => $paymentId,
                'remote_status' => $remoteStatus,
            ]);

            return ['outcome' => 'unmatched', 'inquiry_id' => null];
        }

        $localCentavos = $this->payMongo->toCentavos($local->amount);

        if ($remoteCentavos !== null && $remoteCentavos !== $localCentavos) {
            Log::warning('Payment reconcile: amount differs from provider', [
                'payment_id' => $paymentId,
                'inquiry_id' => $local->inquiry_id,
                'local_centavos' => $localCentavos,
                'remote_centavos' => $remoteCentavos,
            ]);

            return ['outcome' => 'mismatch', 'inquiry_id' => $local->inquiry_id];
        }

        return ['outcome' => 'matched', 'inquiry_id' => $local->inquiry_id, 'remote_status' => $remoteStatus];
    }

    /**
     * Reconcile whatever the inquiry points at (checkout first, then payment).
     *
     * @return array{outcome: string, inquiry_id?: int|null}
     */
    public function reconcileByInquiry(Inquiry $inquiry): array
    {
        $inquiry->refresh();

        if ($inquiry->paymongo_session_id) {
            return $this->reconcileByCheckoutId($inquiry->paymongo_session_id);
        }

        if ($inquiry->paymongo_payment_id) {
            return $this->reconcileByPaymentId($inquiry->paymongo_payment_id);
        }

        return ['outcome' => 'nothing_to_reconcile', 'inquiry_id' => $inquiry->id];
    }

    /**
     * WP-3: sweep abandoned checkouts. For every inquiry holding a
     * payment_pending_amount older than its TTL:
     *
     * - non-confirmed booking → clear outright (leftover; the booking can
     *   no longer be paid and the pending only pollutes verification).
     * - session-backed + stale → re-check via PayMongo: paid → record it
     *   (recovery); unpaid → clear; ambiguous (mismatch/error) → leave and
     *   report for review. Never blindly mutate an ambiguous payment.
     * - session-less legacy + stale + pending == total → clear
     *   (baseline-neutral). Deposit-split pendings without a session are
     *   left for manual review (clearing would move the verification
     *   baseline from the deposit to the total).
     *
     * @return array{cleared: int, recorded: int, skipped: int, needs_review: int}
     */
    public function sweepStalePendings(?int $sessionTtlHours = null, ?int $legacyTtlHours = null): array
    {
        $sessionTtlHours ??= self::CHECKOUT_PENDING_TTL_HOURS;
        $legacyTtlHours ??= self::LEGACY_PENDING_TTL_HOURS;

        $stats = ['cleared' => 0, 'recorded' => 0, 'skipped' => 0, 'needs_review' => 0];

        Inquiry::whereNotNull('payment_pending_amount')
            ->chunkById(100, function ($inquiries) use ($sessionTtlHours, $legacyTtlHours, &$stats) {
                foreach ($inquiries as $inquiry) {
                    $outcome = $this->sweepOnePending($inquiry, $sessionTtlHours, $legacyTtlHours);
                    $stats[$outcome]++;
                }
            });

        return $stats;
    }

    /**
     * @return 'cleared'|'recorded'|'skipped'|'needs_review'
     */
    private function sweepOnePending(Inquiry $inquiry, int $sessionTtlHours, int $legacyTtlHours): string
    {
        // Legacy rows predate payment_pending_at: treat as very old so they
        // are evaluated rather than skipped forever.
        $pendingAt = $inquiry->payment_pending_at ?? $inquiry->updated_at;

        // Non-confirmed bookings can never be paid; a leftover pending only
        // risks a stale verification baseline. Clear at any age.
        if ($inquiry->status !== Inquiry::STATUS_CONFIRMED) {
            $inquiry->update([
                'payment_pending_amount' => null,
                'payment_pending_at' => null,
                'paymongo_session_id' => null,
            ]);

            Log::info('Payment sweep: cleared pending on non-confirmed booking', [
                'inquiry_id' => $inquiry->id,
                'status' => $inquiry->status,
            ]);

            return 'cleared';
        }

        if ($inquiry->paymongo_session_id) {
            if ($pendingAt && $pendingAt->gt(now()->subHours($sessionTtlHours))) {
                return 'skipped';
            }

            $result = $this->reconcileByCheckoutId($inquiry->paymongo_session_id);

            if ($result['outcome'] === 'recorded') {
                return 'recorded';
            }

            if ($result['outcome'] === 'not_paid') {
                $inquiry->refresh()->update([
                    'payment_pending_amount' => null,
                    'payment_pending_at' => null,
                    'paymongo_session_id' => null,
                ]);

                Log::info('Payment sweep: cleared abandoned checkout', [
                    'inquiry_id' => $inquiry->id,
                ]);

                return 'cleared';
            }

            Log::warning('Payment sweep: ambiguous checkout left for review', [
                'inquiry_id' => $inquiry->id,
                'outcome' => $result['outcome'],
            ]);

            return 'needs_review';
        }

        // Session-less legacy pending: only baseline-neutral clears.
        if ($pendingAt && $pendingAt->gt(now()->subHours($legacyTtlHours))) {
            return 'skipped';
        }

        $pending = (float) $inquiry->payment_pending_amount;
        $total = (float) $inquiry->total_amount;

        if (abs($pending - $total) < 0.005) {
            $inquiry->update([
                'payment_pending_amount' => null,
                'payment_pending_at' => null,
            ]);

            Log::info('Payment sweep: cleared stale legacy pending', [
                'inquiry_id' => $inquiry->id,
            ]);

            return 'cleared';
        }

        Log::warning('Payment sweep: deposit-split pending without session left for review', [
            'inquiry_id' => $inquiry->id,
        ]);

        return 'needs_review';
    }

    /**
     * Shared locked credit routine. Turns a checkout-session resource's
     * attributes into local rows (inquiries summary + payments ledger) under
     * a row lock, idempotent on the provider payment id.
     *
     * Returns null when a payment was recorded; otherwise an early-exit array
     * in the webhook's response dialect so the webhook can map it directly:
     * ['ok' => true, 'already_paid' => true] etc. or
     * ['error' => 'Payment amount mismatch'].
     */
    public function creditFromCheckoutResource(Inquiry $inquiry, array $attributes, ?string $sessionId): ?array
    {
        $incomingPaymentId = $attributes['payments'][0]['id'] ?? null;
        $payment = $attributes['payments'][0]['attributes'] ?? [];
        $method = $payment['source']['type'] ?? null;
        $paidCentavos = isset($payment['amount']) ? (int) $payment['amount'] : null;
        $currency = $payment['currency'] ?? $attributes['currency'] ?? 'PHP';

        $payMongo = $this->payMongo;

        try {
            return DB::transaction(function () use ($inquiry, $payMongo, $incomingPaymentId, $method, $paidCentavos, $currency, $sessionId) {
                $locked = Inquiry::where('id', $inquiry->id)->lockForUpdate()->first();

                if ($locked->isPaid()) {
                    Log::channel('stderr')->info('PAYMONGO branch already_paid', ['inquiry_id' => $locked->id]);

                    return ['ok' => true, 'already_paid' => true];
                }

                if ($incomingPaymentId !== null && $locked->paymongo_payment_id === $incomingPaymentId) {
                    Log::channel('stderr')->info('PAYMONGO branch duplicate_payment', [
                        'inquiry_id' => $locked->id,
                        'payment_id' => $incomingPaymentId,
                    ]);

                    return ['ok' => true, 'duplicate_payment' => true];
                }

                // A payment can land after the booking left the confirmed state
                // (e.g. the guest left the checkout open). Terminal bookings
                // (cancelled/expired) enter the WP-6 late-payment workflow:
                // ledger + automatic refund attempt. Pending bookings keep the
                // historical ignore + owner-alert (they may still be
                // confirmed; refunding now would be wrong).
                if ($locked->status !== Inquiry::STATUS_CONFIRMED) {
                    if ($incomingPaymentId !== null && in_array($locked->status, [
                        Inquiry::STATUS_CANCELLED,
                        Inquiry::STATUS_EXPIRED,
                    ], true)) {
                        return $this->handleLatePayment(
                            $locked, $incomingPaymentId, $method,
                            $paidCentavos, $currency, $sessionId
                        );
                    }

                    Log::warning('PayMongo webhook: payment ignored, inquiry not confirmed', [
                        'inquiry_id' => $locked->id,
                        'reference_number' => $locked->reference_code,
                        'status' => $locked->status,
                    ]);

                    return ['ok' => true, 'ignored' => true, 'reason' => 'inquiry_not_confirmed'];
                }

                $expectedCentavos = $payMongo->toCentavos($locked->payment_pending_amount ?? $locked->total_amount);

                if ($paidCentavos === null || $paidCentavos !== $expectedCentavos || $currency !== 'PHP') {
                    Log::warning('PayMongo webhook: amount/currency mismatch; payment NOT recorded', [
                        'inquiry_id' => $locked->id,
                        'reference_number' => $locked->reference_code,
                        'expected_centavos' => $expectedCentavos,
                        'received_centavos' => $paidCentavos,
                        'currency' => $currency,
                    ]);

                    return ['error' => 'Payment amount mismatch'];
                }

                $paidPesos = formatPrice($expectedCentavos / 100, 2, false);
                $newAmountPaid = formatPrice(
                    (float) ($locked->amount_paid ?? 0) + (float) $paidPesos,
                    2, false
                );

                $fullyPaid = (float) $newAmountPaid >= (float) $locked->total_amount;
                $depositCovered = $locked->hasDeposit()
                    && (float) $newAmountPaid >= (float) $locked->deposit_amount;

                $locked->update([
                    'amount_paid' => $newAmountPaid,
                    'payment_pending_amount' => null,
                    'payment_pending_at' => null,
                    'deposit_paid_at' => $depositCovered && ! $locked->isDepositPaid()
                        ? now()
                        : $locked->deposit_paid_at,
                    'fully_paid_at' => $fullyPaid ? now() : $locked->fully_paid_at,
                    'payment_method' => $method,
                    'paymongo_payment_id' => $incomingPaymentId ?? $locked->paymongo_payment_id,
                    'paymongo_session_id' => $sessionId ?? $locked->paymongo_session_id,
                ]);

                // WP-1 dual-write: mirror into the ledger inside the same
                // locked transaction, idempotent on the provider payment id.
                Payment::recordPaid(
                    $locked,
                    $paidPesos,
                    $method,
                    $incomingPaymentId,
                    $sessionId,
                    Payment::classifyType($locked, $fullyPaid, $depositCovered),
                    Payment::PROVIDER_PAYMONGO,
                );

                DB::afterCommit(function () use ($locked) {
                    try {
                        Mail::to($locked->email)->queue(new PaymentReceived($locked));
                    } catch (\Throwable $e) {
                        Log::error('PayMongo webhook: payment recorded but receipt email failed', [
                            'inquiry_id' => $locked->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                });

                return null;
            });
        } catch (\Throwable $e) {
            Log::error('Payment reconcile: failed to record payment', [
                'inquiry_id' => $inquiry->id,
                'error' => $e->getMessage(),
            ]);

            return ['error' => 'Failed to record payment'];
        }
    }

    /**
     * WP-6: a payment that arrived after its booking reached a terminal
     * state. The money is preserved in the ledger (status requires_refund)
     * and an automatic refund is attempted where the provider permits it
     * (always, for PayMongo webhook money). Runs inside the caller's row
     * lock; deduplicates on the provider payment id so redeliveries can
     * never double-credit or double-refund.
     *
     * Returns a webhook-dialect early-exit: ['ok' => true, 'late_payment'
     * => true, 'refund' => 'refunded'|'failed'] (or the amount-mismatch
     * error for garbage payloads, which creates no ledger row).
     */
    private function handleLatePayment(
        Inquiry $locked,
        string $incomingPaymentId,
        ?string $method,
        ?int $paidCentavos,
        string $currency,
        ?string $sessionId,
    ): array {
        if ($paidCentavos === null || $paidCentavos <= 0 || $currency !== 'PHP') {
            Log::warning('Late payment ignored: amount/currency invalid; NOT recorded', [
                'inquiry_id' => $locked->id,
                'reference_number' => $locked->reference_code,
                'received_centavos' => $paidCentavos,
                'currency' => $currency,
            ]);

            return ['error' => 'Payment amount mismatch'];
        }

        $latePesos = formatPrice($paidCentavos / 100, 2, false);
        $hypotheticalPaid = formatPrice((float) ($locked->amount_paid ?? 0) + (float) $latePesos, 2, false);
        $fullyPaid = (float) $hypotheticalPaid >= (float) $locked->total_amount;
        $depositCovered = $locked->hasDeposit()
            && (float) $hypotheticalPaid >= (float) $locked->deposit_amount;

        $ledger = Payment::firstOrCreate(
            ['provider_payment_id' => $incomingPaymentId],
            [
                'inquiry_id' => $locked->id,
                'provider' => Payment::PROVIDER_PAYMONGO,
                'provider_checkout_id' => $sessionId,
                'method' => $method,
                'type' => Payment::classifyType($locked, $fullyPaid, $depositCovered),
                'amount' => $latePesos,
                'currency' => 'PHP',
                'status' => Payment::STATUS_REQUIRES_REFUND,
                'paid_at' => now(),
                'metadata' => ['late_payment' => true, 'inquiry_status' => $locked->status],
            ]
        );

        if (! $ledger->wasRecentlyCreated) {
            Log::channel('stderr')->info('PAYMONGO branch duplicate_late_payment', [
                'inquiry_id' => $locked->id,
                'payment_id' => $incomingPaymentId,
            ]);

            return ['ok' => true, 'duplicate_payment' => true];
        }

        $this->logger->record(
            'payment.late_received',
            $locked,
            "Late payment {$latePesos} for {$locked->reference_code} ({$locked->status}); automatic refund attempted.",
            ['payment_id' => $incomingPaymentId, 'amount' => $latePesos]
        );

        try {
            $refund = $this->payMongo->refundPayment(
                $incomingPaymentId,
                $paidCentavos,
                "late-refund-{$locked->id}-{$incomingPaymentId}"
            );
        } catch (\RuntimeException $e) {
            // Leave requires_refund: WP-7's retry job picks it up. The owner
            // is alerted by the caller (webhook / reconcile command).
            Log::warning('Late payment auto-refund failed; left for retry', [
                'inquiry_id' => $locked->id,
                'payment_id' => $incomingPaymentId,
                'error' => $e->getMessage(),
            ]);

            return ['ok' => true, 'late_payment' => true, 'refund' => 'failed'];
        }

        $refundId = is_array($refund) ? ($refund['id'] ?? null) : null;

        Payment::create([
            'inquiry_id' => $locked->id,
            'provider' => Payment::PROVIDER_PAYMONGO,
            'provider_payment_id' => null,
            'provider_refund_id' => $refundId,
            'method' => $method,
            'type' => Payment::TYPE_REFUND,
            'amount' => $latePesos,
            'currency' => 'PHP',
            'status' => Payment::STATUS_REFUNDED,
            'refunded_at' => now(),
            'metadata' => ['late_payment' => true, 'refunded_payment_id' => $incomingPaymentId],
        ]);

        $ledger->update(['status' => Payment::STATUS_REFUNDED, 'refunded_at' => now()]);

        Log::info('Late payment auto-refunded', [
            'inquiry_id' => $locked->id,
            'payment_id' => $incomingPaymentId,
            'refund_id' => $refundId,
        ]);

        return ['ok' => true, 'late_payment' => true, 'refund' => 'refunded'];
    }

    /**
     * Post-record bookkeeping shared by reconcile entry points (dashboard
     * cache + audit trail). The webhook keeps its own identical lines so its
     * response contract stays byte-identical.
     */
    private function afterRecorded(Inquiry $inquiry): void
    {
        DashboardController::forgetCache();

        $this->logger->record('payment.received', $inquiry, "Payment received for {$inquiry->reference_code}.", [
            'amount_paid' => $inquiry->amount_paid,
            'method' => $inquiry->payment_method,
        ]);

        Log::channel('stderr')->info('PAYMONGO branch recorded', [
            'inquiry_id' => $inquiry->id,
            'reference_number' => $inquiry->reference_code,
        ]);
    }

    private function earlyExitOutcome(array $earlyExit): string
    {
        if (isset($earlyExit['error'])) {
            return $earlyExit['error'] === 'Payment amount mismatch' ? 'mismatch' : 'error';
        }

        foreach (['late_payment', 'already_paid', 'duplicate_payment', 'ignored', 'not_paid'] as $key) {
            if (isset($earlyExit[$key])) {
                return $key;
            }
        }

        return 'ignored';
    }
}
