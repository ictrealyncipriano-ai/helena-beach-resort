<?php

namespace App\Services;

use App\Models\Inquiry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin wrapper around the PayMongo API for the hosted (V2) checkout flow.
 *
 * Creating a V2 checkout session returns a hosted checkout_url the guest is
 * redirected to. PayMongo then sends a `checkout_session.payment.paid` webhook
 * which is verified via the Paymongo-Signature header before processing.
 */
class PayMongoService
{
    public const CURRENCY = 'PHP';
    public const WEBHOOK_SKEW_TOLERANCE = 300;

    /**
     * Create a hosted checkout session for the given (confirmed) inquiry.
     * When an amount is provided it is charged instead of the full total
     * (e.g. a deposit or the remaining balance).
     *
     * @throws \RuntimeException when PayMongo returns an error.
     */
    public function createCheckoutSession(Inquiry $inquiry, ?string $amount = null): array
    {
        $amount = $amount ?? $inquiry->total_amount;
        $centavos = $this->toCentavos($amount);

        if ($centavos < 100 || $centavos > 99999999999) {
            throw new \RuntimeException('This booking has an invalid payable amount and cannot be paid.');
        }

        $response = Http::baseUrl(config('paymongo.base_url'))
            ->withBasicAuth(config('paymongo.secret_key'), '')
            ->acceptJson()
            ->post('/v2/checkout_sessions', [
                'data' => [
                    'attributes' => [
                        'line_items' => [
                            [
                                'name' => $this->lineItemName($inquiry),
                                'amount' => $centavos,
                                'currency' => self::CURRENCY,
                                'quantity' => 1,
                            ],
                        ],
                        'payment_method_types' => config('paymongo.payment_method_types'),
                        'reference_number' => $inquiry->reference_code,
                        'metadata' => ['inquiry_id' => $inquiry->id],
                        'billing' => [
                            'name' => $inquiry->name,
                            'email' => $inquiry->email,
                            'phone' => $inquiry->phone,
                        ],
                        'success_url' => route('booking.portal.show', [$inquiry, 'result' => 'success']),
                        'cancel_url' => route('booking.portal.show', [$inquiry, 'result' => 'cancelled']),
                        'description' => "Booking {$inquiry->reference_code} — {$inquiry->name}",
                    ],
                ],
            ]);

        if ($response->failed()) {
            Log::error('PayMongo checkout session creation failed', [
                'inquiry_id' => $inquiry->id,
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            throw new \RuntimeException('Unable to create a payment session. Please try again later.');
        }

        $session = $response->json('data');

        return [
            'session_id' => $session['id'] ?? null,
            'checkout_url' => $session['attributes']['checkout_url'] ?? null,
        ];
    }

    /**
     * Verify that a webhook request genuinely came from PayMongo.
     *
     * The Paymongo-Signature header carries t (timestamp), te (test) and li
     * (live) signatures. We compute HMAC-SHA256(webhook_secret, "t.payload")
     * and compare against the mode-appropriate value using a timing-safe check.
     */
    public function verifyWebhookSignature(Request $request): bool
    {
        $header = $request->header('Paymongo-Signature');
        $secret = config('paymongo.webhook_secret');

        if (! $header || ! $secret) {
            return false;
        }

        $parts = [];
        foreach (explode(',', $header) as $pair) {
            [$key, $value] = array_pad(explode('=', trim($pair), 2), 2, '');
            $parts[$key] = $value;
        }

        $expected = $this->isLiveMode()
            ? ($parts['li'] ?? null)
            : ($parts['te'] ?? null);

        if (! $expected || empty($parts['t'])) {
            return false;
        }

        $payload = $parts['t'].'.'.$request->getContent();

        if (! hash_equals($expected, hash_hmac('sha256', $payload, $secret))) {
            return false;
        }

        // Reject stale timestamps: a validly-signed but old webhook must not
        // be replayed. Allow 5 minutes of clock skew, then fail closed.
        $timestamp = (int) $parts['t'];

        return $timestamp > 0 && abs(time() - $timestamp) <= self::WEBHOOK_SKEW_TOLERANCE;
    }

    /**
     * Issue a refund for a booking via PayMongo's Refunds API.
     *
     * The refund is always exactly what was collected (refundableAmount()):
     * a deposit-only settlement is refunded the deposit, never the full
     * total. Requires an online PayMongo payment — manually-collected
     * money must be refunded offline by the resort.
     *
     * @throws \RuntimeException when the inquiry has no recorded PayMongo
     *         payment (or nothing refundable), or when PayMongo returns
     *         an error.
     */
    public function refund(Inquiry $inquiry): array
    {
        $paymentId = $inquiry->paymongo_payment_id;

        if (! $paymentId) {
            throw new \RuntimeException('This booking has no PayMongo payment reference, so it cannot be refunded online.');
        }

        $amount = $this->toCentavos($inquiry->refundableAmount());

        if ($amount <= 0) {
            throw new \RuntimeException('This booking has no refundable amount.');
        }

        // Idempotency-Key derived from the inquiry + when it was paid, so a
        // retried refund of the same payment is a no-op at PayMongo rather
        // than a double refund.
        $idempotencyKey = 'refund-'.$inquiry->id.'-'.($inquiry->fully_paid_at?->getTimestamp() ?? $inquiry->deposit_paid_at?->getTimestamp() ?? $inquiry->id);

        $response = Http::baseUrl(config('paymongo.base_url'))
            ->withBasicAuth(config('paymongo.secret_key'), '')
            ->withHeaders(['Idempotency-Key' => $idempotencyKey])
            ->acceptJson()
            ->post('/v1/refunds', [
                'data' => [
                    'attributes' => [
                        'payment_id' => $paymentId,
                        'amount' => $amount,
                        'reason' => 'requested_by_customer',
                    ],
                ],
            ]);

        if ($response->failed()) {
            Log::error('PayMongo refund failed', [
                'inquiry_id' => $inquiry->id,
                'payment_id' => $paymentId,
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            throw new \RuntimeException('Unable to process the refund. Please try again later.');
        }

        return $response->json('data') ?? [];
    }

    private function isLiveMode(): bool
    {
        return str_starts_with((string) config('paymongo.secret_key'), 'sk_live_');
    }

    /**
     * Convert a decimal amount (pesos) to centavos as an integer.
     *
     * Uses exact integer/string math (never `(float)`), so float precision
     * can never corrupt the centavo conversion for normal 2-decimal amounts.
     */
    public function toCentavos(mixed $amount): int
    {
        if ($amount === null || $amount === '') {
            return 0;
        }

        $amount = (string) $amount;
        $parts = explode('.', $amount);
        $whole = (int) $parts[0];
        $fraction = isset($parts[1])
            ? str_pad(substr($parts[1], 0, 2), 2, '0')
            : '00';

        return $whole * 100 + (int) $fraction;
    }

    private function lineItemName(Inquiry $inquiry): string
    {
        $label = $inquiry->booking_type === Inquiry::TYPE_DAY_TOUR ? 'Day Tour' : 'Overnight Stay';
        $cottage = $inquiry->cottage?->name ?? 'Cottage';

        return "{$cottage} — {$label} ({$inquiry->reference_code})";
    }
}
