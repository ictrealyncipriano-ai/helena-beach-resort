<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\GuardsBookingAccess;
use App\Models\Inquiry;
use App\Services\ActivityLogger;
use App\Services\PayMongoService;
use App\Services\PayMongoWebhookService;
use App\Support\Money;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Handles the guest payment flow: redirecting to PayMongo's hosted checkout
 * and receiving the payment webhook.
 */
class PaymentController extends Controller
{
    use GuardsBookingAccess;

    public function __construct(private ActivityLogger $logger) {}

    /**
     * Create a hosted checkout session for a confirmed, unpaid booking and
     * redirect the guest to PayMongo.
     */
    public function pay(Inquiry $inquiry, PayMongoService $payMongo): RedirectResponse
    {
        $this->authorizeBookingAccess($inquiry);

        if ($inquiry->status !== Inquiry::STATUS_CONFIRMED) {
            return redirect()->route('booking.portal.show', $inquiry)
                ->with('error', 'This booking is not confirmed yet and cannot be paid.');
        }

        if ($inquiry->isPaid()) {
            return redirect()->route('booking.portal.show', $inquiry)
                ->with('success', 'This booking has already been paid.');
        }

        // Money Migration: exact string comparisons against the ₱1.00
        // checkout minimum, never binary float. Falsy guard, guard order,
        // messages, and pending-write shape are unchanged.
        if (! $inquiry->total_amount || Money::cmp((string) $inquiry->total_amount, '1.00') < 0) {
            return redirect()->route('booking.portal.show', $inquiry)
                ->with('error', 'This booking has no payable amount set yet. Please contact the resort.');
        }

        $dueNow = $inquiry->amountDueNow();
        if (Money::cmp($dueNow, '1.00') < 0) {
            return redirect()->route('booking.portal.show', $inquiry)
                ->with('error', 'This booking has no outstanding balance.');
        }

        try {
            $session = $payMongo->createCheckoutSession($inquiry, $dueNow);
        } catch (\RuntimeException $e) {
            return redirect()->route('booking.portal.show', $inquiry)
                ->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            // Transport failures (DNS/TLS/timeouts) throw ConnectionException,
            // which is not a RuntimeException — without this the guest gets a
            // bare 500 and PayMongo never sees a session. Never expose the raw
            // exception text; it can leak internals.
            Log::error('PayMongo checkout session transport failure', [
                'inquiry_id' => $inquiry->id,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            return redirect()->route('booking.portal.show', $inquiry)
                ->with('error', 'Unable to reach the payment service. Please try again later.');
        }

        // A malformed 200 can omit checkout_url/session_id; never redirect to
        // null or persist a null session id.
        if (empty($session['checkout_url'])) {
            return redirect()->route('booking.portal.show', $inquiry)
                ->with('error', 'Unable to create a payment session. Please try again later.');
        }

        // Remember what this checkout session is expected to collect so the
        // webhook can verify the paid amount against it (deposit vs balance).
        // Written only after the session exists: persisting earlier would
        // leave a stale expected-amount behind on failure, and that stale
        // value would become the next webhook's verification baseline.
        $inquiry->update([
            'payment_pending_amount' => $dueNow,
            'payment_pending_at' => now(),
            'paymongo_session_id' => $session['session_id'],
        ]);

        return redirect()->away($session['checkout_url']);
    }

    /**
     * PayMongo webhook endpoint. All processing lives in
     * PayMongoWebhookService; this method only renders its [body, status]
     * result as JSON.
     *
     * PayMongo may deliver the checkout session as a raw resource
     * (`data.type` = "checkout_session") or wrapped in an event envelope
     * whose `data.type` is the generic "event" (the real event type, when
     * present, sits at `attributes.type`). All shapes are normalized to the
     * checkout-session attributes before processing, and the paid state is
     * read from the session itself (`paid_at` / payment status) rather than
     * relying on the event-type string.
     */
    public function webhook(Request $request, PayMongoWebhookService $webhooks): JsonResponse
    {
        [$body, $status] = $webhooks->handle($request);

        return response()->json($body, $status);
    }
}
