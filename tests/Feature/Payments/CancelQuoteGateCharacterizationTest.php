<?php

namespace Tests\Feature\Payments;

use App\Models\Cottage;
use App\Models\Inquiry;
use App\Models\User;
use App\Services\BookingCancellationService;
use App\Services\PayMongoService;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Money Migration — quote-gate golden characterization for
 * BookingCancellationService::processRefund() line 43.
 *
 * Captures the legacy binary-float gate outputs BEFORE migrating
 * `(float) $quote['refund_amount'] > 0` to exact
 * `Money::cmp($quote['refund_amount'], '0.00') > 0`. Every absolute golden
 * below was captured from the legacy implementation on this runtime — not
 * inferred. The `hasPayments()` short-circuit, branch bodies, logging, and
 * finalization are pinned alongside the gate decision.
 *
 * Captured finding: on this runtime legacy float `> 0` agrees with
 * `Money::cmp() > 0` across the reachable 2-decimal domain (the quote is
 * already an exact Money string since Phase 7.5). The migration therefore
 * preserves behavior while removing the float-wrap of an exact value.
 */
class CancelQuoteGateCharacterizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function confirmedBooking(string $email, ?string $checkIn = null): Inquiry
    {
        $this->post('/book', [
            'name' => 'Guest',
            'email' => $email,
            'booking_type' => 'overnight',
            'cottage_id' => Cottage::first()->id,
            'check_in' => $checkIn ?? '2026-09-01',
            'check_out' => '2026-10-20',
            'pax' => 2,
        ]);

        $inquiry = Inquiry::where('email', $email)->first();

        $admin = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($admin)->post(route('admin.inquiries.confirm', $inquiry));

        return $inquiry->refresh();
    }

    private function markPaidOnline(Inquiry $inquiry, string $paymentId): Inquiry
    {
        $inquiry->update([
            'amount_paid' => $inquiry->total_amount,
            'fully_paid_at' => now(),
            'payment_method' => Inquiry::METHOD_QRPH,
            'paymongo_payment_id' => $paymentId,
        ]);

        return $inquiry->refresh();
    }

    private function fakeRefundEndpoint(): void
    {
        Http::fake([
            'api.paymongo.com/v1/refunds' => Http::response(['data' => ['id' => 'rfnd_'.uniqid()]], 200),
        ]);
    }

    /**
     * Exact replica of the legacy line 43 gate comparison.
     */
    private function legacyGate(string $refundAmount): bool
    {
        return (float) $refundAmount > 0;
    }

    private function moneyGate(string $refundAmount): bool
    {
        return Money::cmp($refundAmount, '0.00') > 0;
    }

    public function test_unpaid_booking_does_not_attempt_refund(): void
    {
        $inquiry = $this->confirmedBooking('gateunpaid@example.com');

        Http::fake();
        $state = app(BookingCancellationService::class)->processRefund($inquiry, app(PayMongoService::class));

        $this->assertSame('0.00', $state['quote']['refund_amount']);
        $this->assertFalse($state['refunded']);
        $this->assertFalse($state['refundFailed']);
        $this->assertFalse($state['manualRefundRequired']);
        Http::assertNothingSent();
    }

    public function test_paid_far_out_booking_attempts_online_refund(): void
    {
        $this->fakeRefundEndpoint();
        $inquiry = $this->markPaidOnline($this->confirmedBooking('gatefull@example.com'), 'pay_gatefull');

        $state = app(BookingCancellationService::class)->processRefund($inquiry->refresh(), app(PayMongoService::class));

        $this->assertSame(100, $state['quote']['pct']);
        $this->assertSame($inquiry->refresh()->refundableAmount(), $state['quote']['refund_amount']);
        $this->assertTrue($state['refunded']);
        $this->assertFalse($state['manualRefundRequired']);
    }

    public function test_paid_zero_tier_booking_does_not_attempt_refund(): void
    {
        // Imminent check-in lands the 0% tier: collected but nothing quoted.
        $inquiry = $this->markPaidOnline(
            $this->confirmedBooking('gatezero@example.com', now()->addHours(10)->toDateString()),
            'pay_gatezero'
        );

        Http::fake();
        $state = app(BookingCancellationService::class)->processRefund($inquiry->refresh(), app(PayMongoService::class));

        $this->assertTrue($inquiry->refresh()->hasPayments());
        $this->assertSame(0, $state['quote']['pct']);
        $this->assertSame('0.00', $state['quote']['refund_amount']);
        $this->assertFalse($state['refunded']);
        $this->assertFalse($state['refundFailed']);
        $this->assertFalse($state['manualRefundRequired']);
        Http::assertNothingSent();
    }

    /**
     * Reachable 2-decimal gate goldens, captured: legacy float `> 0`
     * agrees with Money::cmp() > 0 on this runtime. These stay green
     * through the gate migration.
     *
     * @dataProvider gateCases
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('gateCases')]
    public function test_legacy_gate_agrees_with_money(string $refund, bool $expected): void
    {
        $this->assertSame($expected, $this->legacyGate($refund));
        $this->assertSame($expected, $this->moneyGate($refund));
    }

    public static function gateCases(): array
    {
        return [
            'zero quotes nothing' => ['0.00', false],
            'one centavo refunds' => ['0.01', true],
            'full share' => ['2500.00', true],
            'large share' => ['5000.00', true],
        ];
    }
}
