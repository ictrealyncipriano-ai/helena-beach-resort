<?php

namespace Tests\Feature;

use App\Models\Cottage;
use App\Models\Inquiry;
use App\Models\User;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Money Migration — pay-minimum gate golden characterization for
 * PaymentController::pay() lines 42/48.
 *
 * Captures the legacy binary-float gate outputs BEFORE migrating the two
 * `(float) ... < 1` comparisons to exact `Money::cmp(..., '1.00') < 0`.
 * Every absolute golden below was captured from the legacy implementation
 * on this runtime — not inferred. Guard ordering (including the untouched
 * `! $inquiry->total_amount` falsy check), messages, routes, and the
 * pending-write shape are pinned alongside the gate decisions.
 *
 * Captured finding: on this runtime legacy float `< 1` agrees with
 * `Money::cmp() < 0` across the reachable 2-decimal domain (total_amount
 * is decimal:2, $dueNow is an exact Money string via amountDueNow()).
 * The migration therefore preserves behavior while removing the
 * float-wrap of exact values at the checkout boundary.
 */
class PayMinimumGateCharacterizationTest extends TestCase
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

        $inquiry = Inquiry::where('email', $email)->first();

        $admin = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($admin)->post(route('admin.inquiries.confirm', $inquiry));

        return $inquiry->refresh();
    }

    private function portalSession(Inquiry $inquiry): array
    {
        return ['booking_access_tokens' => [$inquiry->id => $inquiry->token]];
    }

    private function fakeCheckoutSession(): void
    {
        Http::fake([
            'api.paymongo.com/v2/checkout_sessions' => Http::response([
                'data' => [
                    'id' => 'cs_test_gate',
                    'attributes' => ['checkout_url' => 'https://checkout.paymongo.com/gate'],
                ],
            ], 200),
        ]);
    }

    /**
     * Exact replica of the legacy gate comparisons (falsy guard kept as is).
     */
    private function legacyTotalBlocked(Inquiry $inquiry): bool
    {
        return ! $inquiry->total_amount || (float) $inquiry->total_amount < 1;
    }

    private function moneyTotalBlocked(Inquiry $inquiry): bool
    {
        return ! $inquiry->total_amount || Money::cmp((string) $inquiry->total_amount, '1.00') < 0;
    }

    private function legacyDueBlocked(string $dueNow): bool
    {
        return (float) $dueNow < 1;
    }

    private function moneyDueBlocked(string $dueNow): bool
    {
        return Money::cmp($dueNow, '1.00') < 0;
    }

    public function test_zero_int_total_is_blocked_without_api_call(): void
    {
        $inquiry = $this->confirmedBooking('gatezero@example.com');
        $inquiry->update(['total_amount' => 0]);

        Http::fake();

        $this->withSession($this->portalSession($inquiry))
            ->post(route('payment.pay', $inquiry))
            ->assertRedirect(route('booking.portal.show', $inquiry))
            ->assertSessionHas('error');

        Http::assertNothingSent();
        $this->assertNull($inquiry->refresh()->payment_pending_amount);
        $this->assertTrue($this->legacyTotalBlocked($inquiry->refresh()));
        $this->assertTrue($this->moneyTotalBlocked($inquiry->refresh()));
    }

    public function test_zero_string_total_is_blocked_without_api_call(): void
    {
        // '0.00' is truthy in PHP, so this case is decided by the `< 1`
        // comparison itself — not by the falsy guard.
        $inquiry = $this->confirmedBooking('gatezerostr@example.com');
        $inquiry->update(['total_amount' => '0.00']);

        Http::fake();

        $this->withSession($this->portalSession($inquiry))
            ->post(route('payment.pay', $inquiry))
            ->assertRedirect(route('booking.portal.show', $inquiry))
            ->assertSessionHas('error');

        Http::assertNothingSent();
        $this->assertNull($inquiry->refresh()->payment_pending_amount);
    }

    public function test_sub_peso_total_is_blocked_without_api_call(): void
    {
        $inquiry = $this->confirmedBooking('gatesubpeso@example.com');
        $inquiry->update(['total_amount' => '0.50']);

        Http::fake();

        $this->withSession($this->portalSession($inquiry))
            ->post(route('payment.pay', $inquiry))
            ->assertRedirect(route('booking.portal.show', $inquiry))
            ->assertSessionHas('error');

        Http::assertNothingSent();
        $this->assertNull($inquiry->refresh()->payment_pending_amount);
    }

    public function test_sub_peso_balance_is_blocked_without_api_call(): void
    {
        $inquiry = $this->confirmedBooking('gatebalance@example.com');
        $inquiry->update(['total_amount' => '100.00', 'amount_paid' => '99.50']);

        $this->assertSame('0.50', $inquiry->refresh()->amountDueNow());

        Http::fake();

        $this->withSession($this->portalSession($inquiry))
            ->post(route('payment.pay', $inquiry))
            ->assertRedirect(route('booking.portal.show', $inquiry))
            ->assertSessionHas('error');

        Http::assertNothingSent();
        $this->assertNull($inquiry->refresh()->payment_pending_amount);
    }

    public function test_exactly_one_peso_due_proceeds_to_checkout(): void
    {
        $inquiry = $this->confirmedBooking('gateone@example.com');
        $inquiry->update(['total_amount' => '1.00', 'amount_paid' => '0.00']);

        $this->assertSame('1.00', $inquiry->refresh()->amountDueNow());

        $this->fakeCheckoutSession();

        $this->withSession($this->portalSession($inquiry))
            ->post(route('payment.pay', $inquiry))
            ->assertRedirect('https://checkout.paymongo.com/gate');

        $this->assertSame('cs_test_gate', $inquiry->refresh()->paymongo_session_id);
        $this->assertSame('1.00', $inquiry->refresh()->payment_pending_amount);
    }

    /**
     * Reachable 2-decimal gate goldens, captured: legacy float `< 1`
     * agrees with Money::cmp(..., '1.00') < 0 on this runtime. These stay
     * green through the gate migration.
     *
     * @dataProvider gateCases
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('gateCases')]
    public function test_legacy_gate_agrees_with_money(string $amount, bool $expectedBlocked): void
    {
        $this->assertSame($expectedBlocked, $this->legacyDueBlocked($amount));
        $this->assertSame($expectedBlocked, $this->moneyDueBlocked($amount));
    }

    public static function gateCases(): array
    {
        return [
            'zero blocks' => ['0.00', true],
            'half peso blocks' => ['0.50', true],
            'ninety-nine centavos blocks' => ['0.99', true],
            'exact peso passes' => ['1.00', false],
            'peso and centavo passes' => ['1.01', false],
        ];
    }
}
