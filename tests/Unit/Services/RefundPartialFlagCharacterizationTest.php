<?php

namespace Tests\Unit\Services;

use App\Models\Cottage;
use App\Models\Inquiry;
use App\Models\Payment;
use App\Services\PayMongoService;
use App\Services\RefundService;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Money Migration — partial-flag golden characterization for
 * RefundService::claimAndProcess() line 150.
 *
 * Captures the legacy binary-float comparison outputs BEFORE migrating
 * `(float) $refundAmount < (float) $inquiry->refundableAmount()` to exact
 * `Money::cmp() < 0`. Every absolute golden below was captured from the
 * legacy implementation on this runtime — not inferred.
 *
 * Captured finding: on this runtime legacy float `<` agrees with
 * `Money::cmp() < 0` across the reachable 2-decimal domain (both legs are
 * already exact Money strings via CancellationPolicy::quote() /
 * collectedAmount()). The migration therefore preserves behavior while
 * removing the platform-sensitive float path. Only the status label
 * (partially_refunded vs refunded) moves; amount math, claim guard, and
 * ledger shape are unchanged.
 */
class RefundPartialFlagCharacterizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function paidBooking(string $email): Inquiry
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

        $inquiry = Inquiry::where('email', $email)->first();
        $inquiry->update([
            'status' => Inquiry::STATUS_CONFIRMED,
            'amount_paid' => $inquiry->total_amount,
            'fully_paid_at' => now(),
            'payment_method' => Inquiry::METHOD_QRPH,
            'paymongo_payment_id' => 'pay_'.md5($email),
        ]);

        return $inquiry->refresh();
    }

    private function fakeRefundEndpoint(): void
    {
        Http::fake([
            'api.paymongo.com/v1/refunds' => Http::response(['data' => ['id' => 'rfnd_'.uniqid()]], 200),
        ]);
    }

    private function seedPaidLedger(Inquiry $inquiry, string $suffix): void
    {
        Payment::recordPaid(
            $inquiry->refresh(),
            (string) $inquiry->total_amount,
            'qrph',
            'pay_'.md5($suffix),
            'cs_'.$suffix,
            Payment::TYPE_FULL
        );
        $inquiry->update(['paymongo_payment_id' => 'pay_'.md5($suffix)]);
    }

    /**
     * Exact replica of the legacy line 150 comparison path.
     */
    private function legacyIsPartial(string $refundAmount, string $refundable): bool
    {
        return (float) $refundAmount < (float) $refundable;
    }

    private function moneyIsPartial(string $refundAmount, string $refundable): bool
    {
        return Money::cmp($refundAmount, $refundable) < 0;
    }

    public function test_full_refund_is_not_partial(): void
    {
        $this->fakeRefundEndpoint();
        $inquiry = $this->paidBooking('flagfull@example.com');
        $this->seedPaidLedger($inquiry, 'flagfull@example.com');

        $result = app(RefundService::class)->claimAndProcess($inquiry->refresh(), app(PayMongoService::class));

        $this->assertSame(RefundService::CLAIMED, $result);
        $this->assertSame(
            Payment::STATUS_REFUNDED,
            Payment::where('provider_payment_id', 'pay_'.md5('flagfull@example.com'))->first()->status
        );
    }

    public function test_explicit_equal_amount_is_not_partial(): void
    {
        $this->fakeRefundEndpoint();
        $inquiry = $this->paidBooking('flagequal@example.com');
        $this->seedPaidLedger($inquiry, 'flagequal@example.com');

        $collected = $inquiry->refresh()->refundableAmount();

        $result = app(RefundService::class)->claimAndProcess($inquiry->refresh(), app(PayMongoService::class), $collected);

        $this->assertSame(RefundService::CLAIMED, $result);
        $this->assertSame(
            Payment::STATUS_REFUNDED,
            Payment::where('provider_payment_id', 'pay_'.md5('flagequal@example.com'))->first()->status
        );
        $this->assertFalse($this->legacyIsPartial($collected, $collected));
        $this->assertFalse($this->moneyIsPartial($collected, $collected));
    }

    public function test_half_share_is_partial_and_preserves_amount(): void
    {
        $this->fakeRefundEndpoint();
        $inquiry = $this->paidBooking('flaghalf@example.com');
        $this->seedPaidLedger($inquiry, 'flaghalf@example.com');

        $collected = $inquiry->refresh()->refundableAmount();
        $half = Money::mulPct($collected, 50);

        $result = app(RefundService::class)->claimAndProcess($inquiry->refresh(), app(PayMongoService::class), $half);

        $this->assertSame(RefundService::CLAIMED, $result);
        $this->assertSame(
            Payment::STATUS_PARTIALLY_REFUNDED,
            Payment::where('provider_payment_id', 'pay_'.md5('flaghalf@example.com'))->first()->status
        );

        $refundRow = Payment::where('inquiry_id', $inquiry->id)->where('type', Payment::TYPE_REFUND)->first();
        $this->assertNotNull($refundRow);
        $this->assertSame($half, $refundRow->amount);
        $this->assertTrue($this->legacyIsPartial($half, $collected));
        $this->assertTrue($this->moneyIsPartial($half, $collected));
    }

    /**
     * Reachable 2-decimal comparison goldens, captured: legacy float `<`
     * agrees with Money::cmp() < 0 on this runtime. These stay green
     * through the comparison migration.
     *
     * @dataProvider compareCases
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('compareCases')]
    public function test_legacy_compare_agrees_with_money(string $refund, string $refundable, bool $expectedPartial): void
    {
        $this->assertSame($expectedPartial, $this->legacyIsPartial($refund, $refundable));
        $this->assertSame($expectedPartial, $this->moneyIsPartial($refund, $refundable));
    }

    public static function compareCases(): array
    {
        return [
            'equal amounts' => ['5000.00', '5000.00', false],
            'half share' => ['2500.00', '5000.00', true],
            'odd cents half' => ['617.28', '1234.56', true],
            'one cent less' => ['4999.99', '5000.00', true],
            'one cent more is not partial' => ['5000.01', '5000.00', false],
            'zero of zero' => ['0.00', '0.00', false],
        ];
    }
}
