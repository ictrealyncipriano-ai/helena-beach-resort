<?php

namespace App\Services;

use App\Http\Controllers\Admin\DashboardController;
use App\Mail\InquiryNotification;
use App\Models\Inquiry;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * PayMongo webhook processing: payload normalization, signature gating,
 * and response rendering. The locked money write lives in
 * PaymentReconciliationService::creditFromCheckoutResource (shared with the
 * reconcile command and admin Resync) so there is exactly one place that
 * turns remote money into local rows. Response shapes are part of the
 * PayMongo delivery contract and must stay byte-identical.
 */
class PayMongoWebhookService
{
    public function __construct(
        private PayMongoService $payMongo,
        private ActivityLogger $logger,
        private PaymentReconciliationService $reconciliation,
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

        // The locked money write lives in the shared reconciliation service
        // (single place that turns remote money into local rows). Every guard
        // and the write itself still run under a row lock there; the cheap
        // pre-flight checks above only route obvious misses.
        $sessionId = $event['attributes']['data']['id'] ?? $event['id'] ?? null;
        $earlyExit = $this->reconciliation->creditFromCheckoutResource($inquiry, $attributes, $sessionId);

        // Early exits decided under lock: map them back onto the original
        // response contract PayMongo expects.
        if ($earlyExit !== null) {
            // WP-6: a late payment was preserved in the ledger and an
            // automatic refund attempted. Always alert the owner (success or
            // failure) so unresolved money is visible even before WP-7 retry.
            if (isset($earlyExit['late_payment'])) {
                $ownerEmail = SiteSetting::getValue('contact_email');
                if ($ownerEmail) {
                    try {
                        Mail::to($ownerEmail)->queue(new InquiryNotification($inquiry->refresh()));
                    } catch (\Throwable $e) {
                        Log::error('PayMongo webhook: late-payment alert email failed', [
                            'inquiry_id' => $inquiry->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                return [$earlyExit + ['received' => $received], 200];
            }

            if (($earlyExit['reason'] ?? null) === 'inquiry_not_confirmed') {
                $ownerEmail = SiteSetting::getValue('contact_email');
                if ($ownerEmail) {
                    try {
                        Mail::to($ownerEmail)->queue(new InquiryNotification($inquiry));
                    } catch (\Throwable $e) {
                        Log::error('PayMongo webhook: admin alert email failed', [
                            'inquiry_id' => $inquiry->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }

            // A committed payment that failed to persist must stay an HTTP 500
            // so PayMongo retries (previously thrown; now surfaced as an
            // error early-exit by the shared routine).
            if (($earlyExit['error'] ?? null) === 'Failed to record payment') {
                return [['error' => 'Failed to record payment', 'received' => $received], 500];
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
