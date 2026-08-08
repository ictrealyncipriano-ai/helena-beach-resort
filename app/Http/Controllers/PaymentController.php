<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Concerns\GuardsBookingAccess;
use App\Mail\InquiryNotification;
use App\Mail\PaymentReceived;
use App\Models\Inquiry;
use App\Models\SiteSetting;
use App\Services\PayMongoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Handles the guest payment flow: redirecting to PayMongo's hosted checkout
 * and receiving the payment webhook.
 */
class PaymentController extends Controller
{
    use GuardsBookingAccess;

    /**
     * Create a hosted checkout session for a confirmed, unpaid booking and
     * redirect the guest to PayMongo.
     */
    public function pay(Inquiry $inquiry, PayMongoService $payMongo): RedirectResponse
    {
        $this->authorizeBookingAccess($inquiry);

        if ($inquiry->status !== 'confirmed') {
            return redirect()->route('booking.portal.show', $inquiry)
                ->with('error', 'This booking is not confirmed yet and cannot be paid.');
        }

        if ($inquiry->isPaid()) {
            return redirect()->route('booking.portal.show', $inquiry)
                ->with('success', 'This booking has already been paid.');
        }

        if (! $inquiry->total_amount || (float) $inquiry->total_amount < 1) {
            return redirect()->route('booking.portal.show', $inquiry)
                ->with('error', 'This booking has no payable amount set yet. Please contact the resort.');
        }

        $dueNow = $inquiry->amountDueNow();
        if ((float) $dueNow < 1) {
            return redirect()->route('booking.portal.show', $inquiry)
                ->with('error', 'This booking has no outstanding balance.');
        }

        // Remember what this checkout session is expected to collect so the
        // webhook can verify the paid amount against it (deposit vs balance).
        $inquiry->update(['payment_pending_amount' => $dueNow]);

        try {
            $session = $payMongo->createCheckoutSession($inquiry, $dueNow);
        } catch (\RuntimeException $e) {
            return redirect()->route('booking.portal.show', $inquiry)
                ->with('error', $e->getMessage());
        }

        // A malformed 200 can omit checkout_url/session_id; never redirect to
        // null or persist a null session id.
        if (empty($session['checkout_url'])) {
            return redirect()->route('booking.portal.show', $inquiry)
                ->with('error', 'Unable to create a payment session. Please try again later.');
        }

        $inquiry->update(['paymongo_session_id' => $session['session_id']]);

        return redirect()->away($session['checkout_url']);
    }

    /**
     * PayMongo webhook endpoint. Verifies the signature before doing anything,
     * then records the payment whenever a checkout session reports a paid
     * payment.
     *
     * PayMongo may deliver the checkout session as a raw resource
     * (`data.type` = "checkout_session") or wrapped in an event envelope
     * whose `data.type` is the generic "event" (the real event type, when
     * present, sits at `attributes.type`). All shapes are normalized to the
     * checkout-session attributes before processing, and the paid state is
     * read from the session itself (`paid_at` / payment status) rather than
     * relying on the event-type string.
     */
    public function webhook(Request $request, PayMongoService $payMongo): JsonResponse
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

        if (! $payMongo->verifyWebhookSignature($request)) {
            Log::warning('PayMongo webhook rejected: invalid signature', [
                'type' => $effectiveType,
                'reference_number' => $reference,
            ]);

            return response()->json(['error' => 'Invalid signature', 'received' => $received], 401);
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
                Cache::forget(DashboardController::cacheKey());
            }

            return response()->json(['ok' => true, 'failed' => true, 'received' => $received]);
        }

        if (! in_array($effectiveType, ['event', 'checkout_session', 'checkout_session.payment.paid'], true)) {
            Log::channel('stderr')->info('PAYMONGO branch ignored', [
                'type' => $effectiveType,
                'reference_number' => $reference,
            ]);

            return response()->json(['ok' => true, 'ignored' => true, 'received' => $received]);
        }

        // Only record a payment once the session actually reports one ($paid
        // is computed above).
        if (! $paid) {
            Log::channel('stderr')->info('PAYMONGO branch not_paid', [
                'type' => $effectiveType,
                'reference_number' => $reference,
            ]);

            return response()->json(['ok' => true, 'not_paid' => true, 'received' => $received]);
        }

        $inquiry = Inquiry::where('reference_code', $attributes['reference_number'] ?? null)->first();

        if (! $inquiry) {
            Log::warning('PayMongo webhook: no inquiry found', [
                'reference_number' => $attributes['reference_number'] ?? null,
            ]);

            return response()->json(['error' => 'Inquiry not found', 'received' => $received], 404);
        }

        // Idempotency: ignore repeats of the same payment. A booking is fully
        // settled once paid_at is set; a partial (deposit) payment is matched
        // by its PayMongo payment id so a re-delivered webhook can never
        // credit the same money twice.
        $incomingPaymentId = $attributes['payments'][0]['id'] ?? null;

        if ($inquiry->isPaid()) {
            Log::channel('stderr')->info('PAYMONGO branch already_paid', ['inquiry_id' => $inquiry->id]);

            return response()->json(['ok' => true, 'already_paid' => true, 'received' => $received]);
        }

        if ($incomingPaymentId !== null && $inquiry->paymongo_payment_id === $incomingPaymentId) {
            Log::channel('stderr')->info('PAYMONGO branch duplicate_payment', [
                'inquiry_id' => $inquiry->id,
                'payment_id' => $incomingPaymentId,
            ]);

            return response()->json(['ok' => true, 'duplicate_payment' => true, 'received' => $received]);
        }

        // A payment can land after the booking was cancelled or expired (e.g.
        // the guest left the checkout open). Never record it against a
        // non-confirmed booking — the guest is handled manually, so we only
        // alert the owner and ignore the payment without auto-refunding.
        if ($inquiry->status !== 'confirmed') {
            Log::warning('PayMongo webhook: payment ignored, inquiry not confirmed', [
                'inquiry_id' => $inquiry->id,
                'reference_number' => $inquiry->reference_code,
                'status' => $inquiry->status,
            ]);

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

            return response()->json([
                'ok' => true,
                'ignored' => true,
                'reason' => 'inquiry_not_confirmed',
                'received' => $received,
            ]);
        }

        $payment = $attributes['payments'][0]['attributes'] ?? [];
        $method = $payment['source']['type'] ?? null;

        // Verify the paid amount/currency actually matches what this checkout
        // session was created to collect (the pending amount set at pay()
        // time — deposit or balance — falling back to the full total). A
        // missing, mismatched, or non-PHP amount means the payment must not be
        // trusted as settled — we log a warning and do NOT record anything.
        $expectedCentavos = $payMongo->toCentavos($inquiry->payment_pending_amount ?? $inquiry->total_amount);
        $paidCentavos = isset($payment['amount']) ? (int) $payment['amount'] : null;
        $currency = $payment['currency'] ?? $attributes['currency'] ?? 'PHP';

        if ($paidCentavos === null || $paidCentavos !== $expectedCentavos || $currency !== 'PHP') {
            Log::warning('PayMongo webhook: amount/currency mismatch; payment NOT recorded', [
                'inquiry_id' => $inquiry->id,
                'reference_number' => $inquiry->reference_code,
                'expected_centavos' => $expectedCentavos,
                'received_centavos' => $paidCentavos,
                'currency' => $currency,
            ]);

            return response()->json(['error' => 'Payment amount mismatch', 'received' => $received], 400);
        }

        try {
            // The payment write and the receipt dispatch are transactional:
            // the receipt is only sent after the write has committed, so a
            // rolled-back record can never email a guest. The send itself is
            // wrapped in its own try/catch so a transient mail failure can
            // never turn a committed payment into an HTTP 500 (which would make
            // PayMongo retry, hit the isPaid() branch, and permanently drop
            // the receipt).
            DB::transaction(function () use ($inquiry, $method, $attributes, $event, $expectedCentavos) {
                $paidPesos = number_format($expectedCentavos / 100, 2, '.', '');
                $newAmountPaid = number_format(
                    (float) ($inquiry->amount_paid ?? 0) + (float) $paidPesos,
                    2, '.', ''
                );

                $fullyPaid = (float) $newAmountPaid >= (float) $inquiry->total_amount;
                $depositCovered = $inquiry->hasDeposit()
                    && (float) $newAmountPaid >= (float) $inquiry->deposit_amount;

                $inquiry->update([
                    'amount_paid' => $newAmountPaid,
                    'payment_pending_amount' => null,
                    'deposit_paid_at' => $depositCovered && ! $inquiry->isDepositPaid()
                        ? now()
                        : $inquiry->deposit_paid_at,
                    'paid_at' => $fullyPaid ? now() : $inquiry->paid_at,
                    'fully_paid_at' => $fullyPaid ? now() : null,
                    'paid_amount' => $fullyPaid ? $inquiry->total_amount : $inquiry->paid_amount,
                    'payment_method' => $method,
                    'paymongo_payment_id' => $attributes['payments'][0]['id']
                        ?? $inquiry->paymongo_payment_id,
                    'paymongo_session_id' => $event['attributes']['data']['id']
                        ?? $event['id']
                        ?? $inquiry->paymongo_session_id,
                ]);

                DB::afterCommit(function () use ($inquiry) {
                    try {
                        Mail::to($inquiry->email)->send(new PaymentReceived($inquiry));
                    } catch (\Throwable $e) {
                        Log::error('PayMongo webhook: payment recorded but receipt email failed', [
                            'inquiry_id' => $inquiry->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                });
            });
        } catch (\Throwable $e) {
            Log::error('PayMongo webhook: failed to record payment', [
                'inquiry_id' => $inquiry->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Failed to record payment', 'received' => $received], 500);
        }

        // A payment landing changes the dashboard's paid-this-month and
        // revenue aggregates, so invalidate the cached stats block.
        Cache::forget(DashboardController::cacheKey());

        Log::channel('stderr')->info('PAYMONGO branch recorded', [
            'inquiry_id' => $inquiry->id,
            'reference_number' => $inquiry->reference_code,
        ]);

        return response()->json(['ok' => true, 'received' => $received]);
    }
}
