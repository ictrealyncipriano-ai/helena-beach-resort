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
    /**
     * Create a hosted checkout session for the given (confirmed) inquiry.
     *
     * @throws \RuntimeException when PayMongo returns an error.
     */
    public function createCheckoutSession(Inquiry $inquiry): array
    {
        $amount = $this->toCentavos($inquiry->total_amount);

        if ($amount < 100 || $amount > 99999999999) {
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
                                'amount' => $amount,
                                'currency' => 'PHP',
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
                        'success_url' => route('booking.portal.show', $inquiry),
                        'cancel_url' => route('booking.portal.show', $inquiry),
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

        return hash_equals($expected, hash_hmac('sha256', $payload, $secret));
    }

    public function isLiveMode(): bool
    {
        return str_starts_with((string) config('paymongo.secret_key'), 'sk_live_');
    }

    /**
     * Convert a decimal amount (pesos) to centavos as an integer.
     */
    public function toCentavos(mixed $amount): int
    {
        return (int) round((float) $amount * 100);
    }

    private function lineItemName(Inquiry $inquiry): string
    {
        $label = $inquiry->booking_type === 'day_tour' ? 'Day Tour' : 'Overnight Stay';
        $cottage = $inquiry->cottage?->name ?? 'Cottage';

        return "{$cottage} — {$label} ({$inquiry->reference_code})";
    }
}
