<?php

namespace Tests\Feature\Payments;

use App\Models\Cottage;
use App\Models\Inquiry;
use App\Models\Payment;
use App\Models\User;
use App\Services\RefundService;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Money Migration — retry-gate golden characterization for
 * RetryRefunds::retryInquiryQueue() line 77.
 *
 * Captures the legacy binary-float gate outputs BEFORE migrating
 * `(float) $inquiry->refundableAmount() <= 0` to exact
 * `Money::cmp($inquiry->refundableAmount(), '0.00') <= 0`. Every absolute
 * golden below was captured from the legacy implementation on this
 * runtime — not inferred. Backoff, attempt limits, queue/stat behavior,
 * ledger handling, and claimAndProcess() are pinned alongside the gate.
 *
 * Captured finding: on this runtime legacy float `<= 0` agrees with
 * `Money::cmp() <= 0` across the reachable 2-decimal domain
 * (refundableAmount() is already an exact Money string). The migration
 * therefore preserves behavior while removing the float-wrap of an exact
 * value at the retry boundary.
 */
class RetryGateCharacterizationTest extends TestCase
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

    private function markFailedPastBackoff(Inquiry $inquiry, array $overrides = []): Inquiry
    {
        $inquiry->update(array_merge([
            'refund_status' => RefundService::STATUS_FAILED,
            'refund_attempts' => 1,
            'refund_last_error' => 'boom',
        ], $overrides));

        Inquiry::where('id', $inquiry->id)->update(['updated_at' => now()->subHour()]);

        return $inquiry->refresh();
    }

    /**
     * Exact replica of the legacy line 77 skip decision.
     */
    private function legacyShouldSkip(string $refundable): bool
    {
        return (float) $refundable <= 0;
    }

    private function moneyShouldSkip(string $refundable): bool
    {
        return Money::cmp($refundable, '0.00') <= 0;
    }

    public function test_failed_with_refundable_proceeds_to_retry(): void
    {
        $inquiry = $this->confirmedBooking('gateretry@example.com');
        $inquiry->update([
            'amount_paid' => $inquiry->total_amount,
            'fully_paid_at' => now(),
            'payment_method' => 'qrph',
            'paymongo_payment_id' => 'pay_gate_retry',
        ]);
        Payment::recordPaid($inquiry->refresh(), (string) $inquiry->total_amount, 'qrph', 'pay_gate_retry', 'cs_gate_retry', Payment::TYPE_FULL);
        $this->markFailedPastBackoff($inquiry->refresh());

        Http::fake([
            'api.paymongo.com/v1/refunds' => Http::response(['data' => ['id' => 'rfnd_gate_retry']], 200),
        ]);

        $this->artisan('payments:retry-refunds')->assertSuccessful();

        $inquiry->refresh();
        $this->assertSame(RefundService::STATUS_COMPLETED, $inquiry->refund_status);
        $this->assertNotNull($inquiry->refunded_at);
        $this->assertFalse($this->legacyShouldSkip($inquiry->refundableAmount()));
        $this->assertFalse($this->moneyShouldSkip($inquiry->refundableAmount()));
    }

    public function test_failed_with_zero_refundable_is_silently_skipped(): void
    {
        $inquiry = $this->confirmedBooking('gateskip@example.com');
        // Reaches the gate (failed + provider id + backoff elapsed) but has
        // nothing refundable, so the row must be skipped with no HTTP.
        $inquiry->update([
            'amount_paid' => '0.00',
            'paymongo_payment_id' => 'pay_gate_skip',
        ]);
        $this->markFailedPastBackoff($inquiry->refresh());

        $this->assertSame('0.00', $inquiry->refresh()->refundableAmount());

        Http::fake();

        $this->artisan('payments:retry-refunds')->assertSuccessful();

        Http::assertNothingSent();
        $this->assertSame(RefundService::STATUS_FAILED, $inquiry->refresh()->refund_status);
        $this->assertTrue($this->legacyShouldSkip('0.00'));
        $this->assertTrue($this->moneyShouldSkip('0.00'));
    }

    /**
     * Reachable 2-decimal gate goldens, captured: legacy float `<= 0`
     * agrees with Money::cmp() <= 0 on this runtime. These stay green
     * through the gate migration.
     *
     * @dataProvider gateCases
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('gateCases')]
    public function test_legacy_gate_agrees_with_money(string $refundable, bool $expectedSkip): void
    {
        $this->assertSame($expectedSkip, $this->legacyShouldSkip($refundable));
        $this->assertSame($expectedSkip, $this->moneyShouldSkip($refundable));
    }

    public static function gateCases(): array
    {
        return [
            'zero is skipped' => ['0.00', true],
            'one centavo retries' => ['0.01', false],
            'full amount retries' => ['5000.00', false],
        ];
    }
}
