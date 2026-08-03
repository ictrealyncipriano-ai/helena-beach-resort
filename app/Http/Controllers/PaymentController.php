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
     * then records the payment for `checkout_session.payment.paid` events.
     */
    public function webhook(Request $request, PayMongoService $payMongo): JsonResponse
    {
        if (! $payMongo->verifyWebhookSignature($request)) {
            Log::warning('PayMongo webhook rejected: invalid signature');

            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $event = $request->json('data');
        if (($event['type'] ?? null) !== 'checkout_session.payment.paid') {
            return response()->json(['ok' => true, 'ignored' => true]);
        }

        $attributes = $event['data']['attributes'] ?? [];
        $inquiry = Inquiry::where('reference_code', $attributes['reference_number'] ?? null)->first();

        if (! $inquiry) {
            Log::warning('PayMongo webhook: no inquiry found', [
                'reference_number' => $attributes['reference_number'] ?? null,
            ]);

            return response()->json(['error' => 'Inquiry not found'], 404);
        }

        // Idempotency: ignore repeats of the same payment.
        if ($inquiry->isPaid()) {
            return response()->json(['ok' => true, 'already_paid' => true]);
        }

        $payment = $attributes['payments'][0]['attributes'] ?? [];
        $method = $payment['source']['type'] ?? null;

        $inquiry->update([
            'paid_at' => now(),
            'paid_amount' => isset($payment['amount'])
                ? $payment['amount'] / 100
                : $inquiry->total_amount,
            'payment_method' => $method,
            'paymongo_session_id' => $event['data']['id'] ?? $inquiry->paymongo_session_id,
        ]);

        try {
            Mail::to($inquiry->email)->send(new PaymentReceived($inquiry));
        } catch (\Exception $e) {
            Log::warning('Failed to send payment receipt', [
                'inquiry_id' => $inquiry->id,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json(['ok' => true]);
    }
}
