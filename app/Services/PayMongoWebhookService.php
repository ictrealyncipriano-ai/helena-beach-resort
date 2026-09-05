<?php

namespace App\Services;

use App\Http\Controllers\Admin\DashboardController;
use App\Mail\InquiryNotification;
use App\Mail\PaymentReceived;
use App\Models\Inquiry;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * PayMongo webhook processing: payload normalization, signature gating,
 * idempotent recording under a row lock, and post-commit notifications.
 *
 * Extracted verbatim from PaymentController@webhook so the controller only
 * verifies delivery and renders the JSON response. Response shapes are part
 * of the PayMongo delivery contract and must stay byte-identical.
 */
class PayMongoWebhookService
{
    public function __construct(
        private PayMongoService $payMongo,
        private ActivityLogger $logger,
    ) {
    }

    /**
     * @return array{0: array, 1: int} [response body, HTTP status]
     */
    public function handle(Request $request): array
    {
        $body = $request->json()?->all() ?? [];

        // PayMongo may (a) deliver the checkout session directly with no
        // wrapper `{id, type, attributes}`, (b) wrap it in an outer `data`,
        // or (c) wrap it in an event envelope whose `data.attributes.data`
        // holds the resource. Drop one wrapper level, then read the resource
        // attributes whichever way they arrive.
        $event = $body['data'] ?? $body;
        $type = $event['type'] ?? null;

        $resourceAttrs = $event['attributes']['data']['attributes'] ?? null;
        $eventAttrs = $event['attributes'] ?? null;
        $subType = $event['attributes']['type'] ?? null;

        $shape = $resourceAttrs !== null
            ? 'event-wrapper'
            : ($eventAttrs !== null
                ? 'resource'
                : 'none');

        $attributes = $resourceAttrs ?? $eventAttrs ?? [];

        // The envelope object uses `type: "event"`; the actual event type is
        // carried under `attributes.type` when present. Fall back to the
        // wrapper's type otherwise.
        $effectiveType = $subType ?? $type;

        $reference = $attributes['reference_number']
            ?? $attributes['external_reference_number'] ?? null;

        $paid = ! empty($attributes['paid_at'])
            || ($attributes['payments'][0]['attributes']['status'] ?? null) === 'paid';

        $received = [
            'type' => $effectiveType,
            'raw_type' => $type,
            'shape' => $shape,
            'reference_number' => $reference,
            'paid' => $paid,
            'top_level_keys' => array_keys($event),
            'resource_has_reference' => isset($event['attributes']['data']['attributes']['reference_number']),
            'data_has_reference' => isset($event['data']['attributes']['reference_number']),
        ];

        // Security: this endpoint never logs the raw payload (it contains
        // guest name/email/phone/amount). Only non-sensitive identifiers and
        // the outcome are written, and each branch logs a single line.
        Log::channel('stderr')->info('PAYMONGO webhook enter', [
            'type' => $effectiveType,
            'shape' => $shape,
            'reference_number' => $reference,
        ]);

        if (! $this->payMongo->verifyWebhookSignature($request)) {
            Log::warning('PayMongo webhook rejected: invalid signature', [
                'type' => $effectiveType,
                'reference_number' => $reference,
            ]);

            return [['error' => 'Invalid signature', 'received' => $received], 401];
        }

        Log::channel('stderr')->info('PAYMONGO signature ok', [
            'type' => $effectiveType,
            'reference_number' => $reference,
        ]);

        if ($effectiveType === 'payment.failed') {
            $reference = $attributes['external_reference_number'] ?? $attributes['reference_number'] ?? null;

            $inquiry = $reference
                ? Inquiry::where('reference_code', $reference)->first()
                : null;

            Log::warning('PayMongo payment failed', [
                'inquiry_id' => $inquiry?->id,
                'reference_number' => $reference,
            ]);

            if ($inquiry && ! $inquiry->isPaid() && ! $inquiry->hasFailedPayment()) {
                $inquiry->update(['payment_failed_at' => now()]);

                // The dashboard's paid/revenue aggregates are unchanged by a
                // failed payment, but the booking's payment state is part of
                // the stats block — drop it defensively like the paid branch.
                DashboardController::forgetCache();
            }

            return [['ok' => true, 'failed' => true, 'received' => $received], 200];
        }

        if (! in_array($effectiveType, ['event', 'checkout_session', 'checkout_session.payment.paid'], true)) {
            Log::channel('stderr')->info('PAYMONGO branch ignored', [
                'type' => $effectiveType,
                'reference_number' => $reference,
            ]);

            return [['ok' => true, 'ignored' => true, 'received' => $received], 200];
        }

        // Only record a payment once the session actually reports one ($paid
        // is computed above).
        if (! $paid) {
            Log::channel('stderr')->info('PAYMONGO branch not_paid', [
                'type' => $effectiveType,
                'reference_number' => $reference,
            ]);

            return [['ok' => true, 'not_paid' => true, 'received' => $received], 200];
        }

        // Resolve the booking the same way the failed branch does: PayMongo
        // may put the reference in either attribute depending on event shape.
        $webhookReference = $attributes['reference_number'] ?? $attributes['external_reference_number'] ?? null;

        $inquiry = $webhookReference
            ? Inquiry::where('reference_code', $webhookReference)->first()
            : null;

        if (! $inquiry) {
            Log::warning('PayMongo webhook: no inquiry found', [
                'reference_number' => $webhookReference,
            ]);

            return [['error' => 'Inquiry not found', 'received' => $received], 404];
        }

        $incomingPaymentId = $attributes['payments'][0]['id'] ?? null;
        $payment = $attributes['payments'][0]['attributes'] ?? [];
        $method = $payment['source']['type'] ?? null;

        // Verify the paid amount/currency actually matches what this checkout
        // session was created to collect (the pending amount set at pay()
        // time — deposit or balance — falling back to the full total). A
        // missing, mismatched, or non-PHP amount means the payment must not
        // be trusted as settled — we log a warning and do NOT record anything.
        $paidCentavos = isset($payment['amount']) ? (int) $payment['amount'] : null;
        $currency = $payment['currency'] ?? $attributes['currency'] ?? 'PHP';

        try {
            // Every guard and the write itself run again inside a row lock:
            // two concurrent deliveries of the same payment must be able to
            // credit exactly once. The cheap pre-flight checks above only
            // route obvious misses; the lock below is the real gatekeeper
            // (backed by the unique index on paymongo_payment_id).
            $payMongo = $this->payMongo;
            $earlyExit = DB::transaction(function () use ($inquiry, $payMongo, $incomingPaymentId, $method, $paidCentavos, $currency, $attributes, $event) {
                $locked = Inquiry::where('id', $inquiry->id)->lockForUpdate()->first();

                // Idempotency: ignore repeats of the same payment. A booking is
                // fully settled once paid_at is set; a partial (deposit) payment
                // is matched by its PayMongo payment id so a re-delivered
                // webhook can never credit the same money twice.
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

                // A payment can land after the booking was cancelled or expired
                // (e.g. the guest left the checkout open). Never record it against
                // a non-confirmed booking — the guest is handled manually, so we
                // only alert the owner and ignore the payment without refunding.
                if ($locked->status !== Inquiry::STATUS_CONFIRMED) {
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

                // The payment write and the receipt dispatch are transactional:
                // the receipt is only sent after the write has committed, so a
                // rolled-back record can never email a guest. The send itself is
                // wrapped in its own try/catch so a transient mail failure can
                // never turn a committed payment into an HTTP 500 (which would
                // make PayMongo retry, hit the already_paid branch, and
                // permanently drop the receipt).
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
                    'deposit_paid_at' => $depositCovered && ! $locked->isDepositPaid()
                        ? now()
                        : $locked->deposit_paid_at,
                    'fully_paid_at' => $fullyPaid ? now() : $locked->fully_paid_at,
                    'payment_method' => $method,
                    'paymongo_payment_id' => $attributes['payments'][0]['id']
                        ?? $locked->paymongo_payment_id,
                    'paymongo_session_id' => $event['attributes']['data']['id']
                        ?? $event['id']
                        ?? $locked->paymongo_session_id,
                ]);

                DB::afterCommit(function () use ($locked) {
                    try {
                        Mail::to($locked->email)->send(new PaymentReceived($locked));
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
            Log::error('PayMongo webhook: failed to record payment', [
                'inquiry_id' => $inquiry->id,
                'error' => $e->getMessage(),
            ]);

            return [['error' => 'Failed to record payment', 'received' => $received], 500];
        }

        // Early exits decided under lock: map them back onto the original
        // response contract PayMongo expects.
        if ($earlyExit !== null) {
            if (($earlyExit['reason'] ?? null) === 'inquiry_not_confirmed') {
                $ownerEmail = SiteSetting::getValue('contact_email');
                if ($ownerEmail) {
                    try {
                        Mail::to($ownerEmail)->send(new InquiryNotification($inquiry));
                    } catch (\Throwable $e) {
                        Log::error('PayMongo webhook: admin alert email failed', [
                            'inquiry_id' => $inquiry->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }

            $status = isset($earlyExit['error']) ? 400 : 200;

            return [$earlyExit + ['received' => $received], $status];
        }

        $inquiry->refresh();

        // A payment landing changes the dashboard's paid-this-month and
        // revenue aggregates, so invalidate the cached stats block.
        DashboardController::forgetCache();

        $this->logger->record('payment.received', $inquiry, "Payment received for {$inquiry->reference_code}.", [
            'amount_paid' => $inquiry->amount_paid,
            'method' => $inquiry->payment_method,
        ]);

        Log::channel('stderr')->info('PAYMONGO branch recorded', [
            'inquiry_id' => $inquiry->id,
            'reference_number' => $inquiry->reference_code,
        ]);

        return [['ok' => true, 'received' => $received], 200];
    }
}
