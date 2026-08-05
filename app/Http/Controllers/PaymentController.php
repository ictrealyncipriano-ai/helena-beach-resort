<?php

namespace App\Http\Controllers;

use App\Mail\PaymentReceived;
use App\Models\Inquiry;
use App\Services\PayMongoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Handles the guest payment flow: redirecting to PayMongo's hosted checkout
 * and receiving the payment webhook.
 */
class PaymentController extends Controller
{
    /**
     * Create a hosted checkout session for a confirmed, unpaid booking and
     * redirect the guest to PayMongo.
     */
    public function pay(Inquiry $inquiry, PayMongoService $payMongo): RedirectResponse
    {
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

        try {
            $session = $payMongo->createCheckoutSession($inquiry);
        } catch (\RuntimeException $e) {
            return redirect()->route('booking.portal.show', $inquiry)
                ->with('error', $e->getMessage());
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

        Log::channel('stderr')->info('PAYMONGO webhook enter', [
            'received' => $received,
            'body_has_data_wrapper' => array_key_exists('data', $body),
            'body_keys' => array_keys($body),
            'raw_body' => substr($request->getContent(), 0, 4000),
        ]);

        if (! $payMongo->verifyWebhookSignature($request)) {
            Log::warning('PayMongo webhook rejected: invalid signature');
            Log::channel('stderr')->info('PAYMONGO branch invalid_signature', ['received' => $received]);

            return response()->json(['error' => 'Invalid signature', 'received' => $received], 401);
        }

        Log::channel('stderr')->info('PAYMONGO signature ok', ['received' => $received]);

        if ($effectiveType === 'payment.failed') {
            $reference = $attributes['external_reference_number'] ?? $attributes['reference_number'] ?? null;

            $inquiry = $reference
                ? Inquiry::where('reference_code', $reference)->first()
                : null;

            Log::warning('PayMongo payment failed', [
                'inquiry_id' => $inquiry?->id,
                'reference_number' => $reference,
                'amount' => $attributes['amount'] ?? null,
                'source' => $attributes['source']['type'] ?? null,
            ]);

            if ($inquiry && ! $inquiry->isPaid() && ! $inquiry->hasFailedPayment()) {
                $inquiry->update(['payment_failed_at' => now()]);
            }

            return response()->json(['ok' => true, 'failed' => true, 'received' => $received]);
        }

        if (! in_array($effectiveType, ['event', 'checkout_session', 'checkout_session.payment.paid'], true)) {
            Log::channel('stderr')->info('PAYMONGO branch ignored', ['received' => $received]);

            return response()->json(['ok' => true, 'ignored' => true, 'received' => $received]);
        }

        // Only record a payment once the session actually reports one ($paid
        // is computed above).
        if (! $paid) {
            Log::channel('stderr')->info('PAYMONGO branch not_paid', ['received' => $received]);

            return response()->json(['ok' => true, 'not_paid' => true, 'received' => $received]);
        }

        $inquiry = Inquiry::where('reference_code', $attributes['reference_number'] ?? null)->first();

        if (! $inquiry) {
            Log::warning('PayMongo webhook: no inquiry found', [
                'reference_number' => $attributes['reference_number'] ?? null,
            ]);
            Log::channel('stderr')->info('PAYMONGO branch no_inquiry', ['received' => $received]);

            return response()->json(['error' => 'Inquiry not found', 'received' => $received], 404);
        }

        // Idempotency: ignore repeats of the same payment.
        if ($inquiry->isPaid()) {
            Log::channel('stderr')->info('PAYMONGO branch already_paid', ['received' => $received, 'inquiry_id' => $inquiry->id]);

            return response()->json(['ok' => true, 'already_paid' => true, 'received' => $received]);
        }

        $payment = $attributes['payments'][0]['attributes'] ?? [];
        $method = $payment['source']['type'] ?? null;

        try {
            $inquiry->update([
                'paid_at' => now(),
                'paid_amount' => isset($payment['amount'])
                    ? $payment['amount'] / 100
                    : $inquiry->total_amount,
                'payment_method' => $method,
                'paymongo_payment_id' => $attributes['payments'][0]['id']
                    ?? $inquiry->paymongo_payment_id,
                'paymongo_session_id' => $event['attributes']['data']['id']
                    ?? $event['id']
                    ?? $inquiry->paymongo_session_id,
            ]);
        } catch (\Throwable $e) {
            Log::error('PayMongo webhook: failed to record payment', [
                'inquiry_id' => $inquiry->id,
                'error' => $e->getMessage(),
            ]);
            Log::channel('stderr')->info('PAYMONGO branch update_failed', [
                'inquiry_id' => $inquiry->id,
                'error' => $e->getMessage(),
                'received' => $received,
            ]);

            return response()->json(['error' => 'Failed to record payment', 'received' => $received], 500);
        }

        try {
            Mail::to($inquiry->email)->send(new PaymentReceived($inquiry));
        } catch (\Exception $e) {
            Log::warning('Failed to send payment receipt', [
                'inquiry_id' => $inquiry->id,
                'error' => $e->getMessage(),
            ]);
        }

        Log::channel('stderr')->info('PAYMONGO branch recorded', ['inquiry_id' => $inquiry->id, 'received' => $received]);

        return response()->json(['ok' => true, 'received' => $received]);
    }
}
