<?php

namespace Tests\Feature\Payments;

use App\Models\Cottage;
use App\Models\Inquiry;
use App\Models\Payment;
use App\Models\User;
use App\Services\PaymentReconciliationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * WP-3: abandoned checkouts cannot permanently hold inventory.
 * Stale pendings are re-checked remotely and cleared only when safe.
 */
class CheckoutExpiryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function confirmedBooking(string $email): Inquiry
    {
        $this->post('/book', [
            'name' => 'Guest',
            'email' => $email,
            'booking_type' => 'overnight',
            'cottage_id' => Cottage::first()->id,
            'check_in' => '2026-09-01',
            'check_out' => '2026-09-03',
            'pax' => 2,
        ]);

        $inquiry = Inquiry::where('email', $email)->firstOrFail();

        $admin = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($admin)->post(route('admin.inquiries.confirm', $inquiry));

        return $inquiry->refresh();
    }

    private function portalSession(Inquiry $inquiry): array
    {
        return ['booking_access_tokens' => [$inquiry->id => $inquiry->token]];
    }

    private function paidSession(Inquiry $inquiry, string $sessionId, string $paymentId, int $centavos): array
    {
        return [
            'id' => $sessionId,
            'type' => 'checkout_session',
            'attributes' => [
                'reference_number' => $inquiry->reference_code,
                'currency' => 'PHP',
                'paid_at' => 1785892089,
                'payments' => [
                    [
                        'id' => $paymentId,
                        'attributes' => ['status' => 'paid', 'amount' => $centavos, 'currency' => 'PHP', 'source' => ['type' => 'qrph']],
                    ],
                ],
            ],
        ];
    }

    private function unpaidSession(Inquiry $inquiry, string $sessionId): array
    {
        return [
            'id' => $sessionId,
            'type' => 'checkout_session',
            'attributes' => [
                'reference_number' => $inquiry->reference_code,
                'currency' => 'PHP',
                'paid_at' => null,
                'payments' => [],
            ],
        ];
    }

    public function test_pay_sets_pending_timestamp(): void
    {
        $inquiry = $this->confirmedBooking('exppay@example.com');

        Http::fake([
            'api.paymongo.com/v2/checkout_sessions' => Http::response([
                'data' => [
                    'id' => 'cs_exp_ts',
                    'attributes' => ['checkout_url' => 'https://checkout.paymongo.com/exp'],
                ],
            ], 200),
        ]);

        $this->withSession($this->portalSession($inquiry))
            ->post(route('payment.pay', $inquiry))
            ->assertRedirect('https://checkout.paymongo.com/exp');

        $this->assertNotNull($inquiry->refresh()->payment_pending_at);
    }

    public function test_sweep_clears_stale_unpaid_checkout_but_keeps_booking_payable(): void
    {
        $inquiry = $this->confirmedBooking('expclear@example.com');
        $inquiry->update([
            'payment_pending_amount' => $inquiry->total_amount,
            'payment_pending_at' => now()->subHours(5),
            'paymongo_session_id' => 'cs_exp_clear',
        ]);

        Http::fake([
            'api.paymongo.com/v2/checkout_sessions/*' => Http::response([
                'data' => $this->unpaidSession($inquiry->refresh(), 'cs_exp_clear'),
            ], 200),
        ]);

        $stats = app(PaymentReconciliationService::class)->sweepStalePendings();

        $this->assertSame(1, $stats['cleared']);

        $inquiry->refresh();
        $this->assertNull($inquiry->payment_pending_amount);
        $this->assertNull($inquiry->payment_pending_at);
        $this->assertNull($inquiry->paymongo_session_id);
        // Booking itself is untouched and can start a fresh checkout.
        $this->assertSame(Inquiry::STATUS_CONFIRMED, $inquiry->status);
        $this->assertSame(0, Payment::where('inquiry_id', $inquiry->id)->count());
    }

    public function test_sweep_records_stale_checkout_that_is_actually_paid(): void
    {
        $inquiry = $this->confirmedBooking('exppaid@example.com');
        $inquiry->update([
            'payment_pending_amount' => $inquiry->total_amount,
            'payment_pending_at' => now()->subHours(5),
            'paymongo_session_id' => 'cs_exp_paid',
        ]);
        $inquiry->refresh();

        Http::fake([
            'api.paymongo.com/v2/checkout_sessions/*' => Http::response([
                'data' => $this->paidSession(
                    $inquiry, 'cs_exp_paid', 'pay_exp_paid',
                    (int) round((float) $inquiry->total_amount * 100)
                ),
            ], 200),
        ]);

        $stats = app(PaymentReconciliationService::class)->sweepStalePendings();

        $this->assertSame(1, $stats['recorded']);
        $this->assertTrue($inquiry->refresh()->isPaid());
    }

    public function test_sweep_skips_fresh_pending_without_calling_api(): void
    {
        $inquiry = $this->confirmedBooking('expfresh@example.com');
        $inquiry->update([
            'payment_pending_amount' => $inquiry->total_amount,
            'payment_pending_at' => now()->subHour(),
            'paymongo_session_id' => 'cs_exp_fresh',
        ]);

        Http::fake();

        $stats = app(PaymentReconciliationService::class)->sweepStalePendings();

        $this->assertSame(1, $stats['skipped']);
        Http::assertNothingSent();
        $this->assertNotNull($inquiry->refresh()->payment_pending_amount);
    }

    public function test_sweep_clears_pending_on_cancelled_booking_without_api_call(): void
    {
        $inquiry = $this->confirmedBooking('expcancelled@example.com');
        $inquiry->update([
            'payment_pending_amount' => $inquiry->total_amount,
            'payment_pending_at' => now()->subHour(),
            'paymongo_session_id' => 'cs_exp_cancelled',
        ]);

        $this->withSession($this->portalSession($inquiry))
            ->post(route('booking.portal.cancel', $inquiry));
        $this->assertSame(Inquiry::STATUS_CANCELLED, $inquiry->refresh()->status);

        // Simulate a leftover pending (e.g. cancel raced a checkout create).
        $inquiry->update([
            'payment_pending_amount' => $inquiry->total_amount,
            'payment_pending_at' => now(),
            'paymongo_session_id' => 'cs_exp_cancelled',
        ]);

        Http::fake();

        $stats = app(PaymentReconciliationService::class)->sweepStalePendings();

        $this->assertSame(1, $stats['cleared']);
        Http::assertNothingSent();
        $this->assertNull($inquiry->refresh()->payment_pending_amount);
    }

    public function test_sweep_clears_stale_legacy_pending_equal_to_total(): void
    {
        $inquiry = $this->confirmedBooking('explegacy@example.com');
        $inquiry->update([
            'payment_pending_amount' => $inquiry->total_amount,
            'paymongo_session_id' => null,
        ]);
        // Age the row at the query level (model update would bump updated_at).
        Inquiry::where('id', $inquiry->id)->update([
            'payment_pending_at' => null,
            'updated_at' => now()->subHours(30),
        ]);

        Http::fake();

        $stats = app(PaymentReconciliationService::class)->sweepStalePendings();

        $this->assertSame(1, $stats['cleared']);
        Http::assertNothingSent();
        $this->assertNull($inquiry->refresh()->payment_pending_amount);
    }

    public function test_sweep_leaves_deposit_split_legacy_pending_for_review(): void
    {
        $inquiry = $this->confirmedBooking('expreview@example.com');
        $inquiry->update([
            'deposit_amount' => '1500.00',
            'payment_pending_amount' => '1500.00',
            'paymongo_session_id' => null,
        ]);
        Inquiry::where('id', $inquiry->id)->update([
            'payment_pending_at' => null,
            'updated_at' => now()->subHours(30),
        ]);

        Http::fake();

        $stats = app(PaymentReconciliationService::class)->sweepStalePendings();

        // Clearing would move the verification baseline from the deposit to
        // the total, so the row is reported, never mutated.
        $this->assertSame(1, $stats['needs_review']);
        $this->assertSame('1500.00', (string) $inquiry->refresh()->payment_pending_amount);
    }
}
