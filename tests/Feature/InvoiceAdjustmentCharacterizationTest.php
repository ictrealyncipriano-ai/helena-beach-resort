<?php

namespace Tests\Feature;

use App\Models\Cottage;
use App\Models\Inquiry;
use App\Models\PromoCode;
use App\Models\User;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Money Migration — invoice-adjustment golden characterization for
 * resources/views/pages/invoice.blade.php lines 238/240.
 *
 * Captures the legacy binary-float adjustment outputs BEFORE migrating
 * the `round((float) ...)` calculation to exact `Money::sub()` and the
 * `abs() >= 0.01` visibility gate to `Money::cmp() !== 0`. Every absolute
 * golden below was captured from the legacy implementation on this
 * runtime — not inferred. Subtotal construction, formatPrice() rendering,
 * Total Due, the :231 discount row, and persistence are pinned alongside.
 *
 * Captured finding: on this runtime the legacy expression agrees with
 * the exact Money expression across the reachable 2-decimal domain —
 * including negative adjustments and the visibility boundary. The
 * migration therefore preserves behavior while removing the float seam
 * from display-only reconciliation math.
 */
class InvoiceAdjustmentCharacterizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Mail::fake();
    }

    private function confirmedBooking(string $email, array $overrides = []): Inquiry
    {
        $this->post('/book', array_merge([
            'name' => 'Invoice Guest',
            'email' => $email,
            'booking_type' => 'overnight',
            'cottage_id' => Cottage::first()->id,
            'check_in' => '2026-09-01',
            'check_out' => '2026-09-03',
            'pax' => 2,
        ], $overrides));

        $inquiry = Inquiry::where('email', $email)->first();

        $admin = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($admin)->post(route('admin.inquiries.confirm', $inquiry));

        return $inquiry->refresh();
    }

    private function portalSession(Inquiry $inquiry): array
    {
        return ['booking_access_tokens' => [$inquiry->id => ['token' => $inquiry->token, 'granted_at' => now()->toDateTimeString()]]];
    }

    /**
     * Exact replica of the legacy :238-240 seam.
     *
     * @return array{adjustment: string, show: bool}
     */
    private function legacyAdjustment(string $total, string $subtotal, string $discount): array
    {
        $adjustment = round((float) $total - ((float) $subtotal - (float) $discount), 2);

        return [
            'adjustment' => number_format($adjustment, 2, '.', ''),
            'show' => abs($adjustment) >= 0.01,
        ];
    }

    /**
     * Exact replica of the planned Money seam.
     *
     * @return array{adjustment: string, show: bool}
     */
    private function moneyAdjustment(string $total, string $subtotal, string $discount): array
    {
        $adjustment = Money::sub($total, Money::sub($subtotal, $discount));

        return [
            'adjustment' => $adjustment,
            'show' => Money::cmp($adjustment, '0.00') !== 0,
        ];
    }

    public function test_plain_booking_shows_no_adjustment_row(): void
    {
        $inquiry = $this->confirmedBooking('adjplain@example.com');

        $this->withSession($this->portalSession($inquiry))
            ->get(route('invoice.show', $inquiry))
            ->assertOk()
            ->assertSee('Subtotal', false)
            ->assertSee('Total Due', false)
            ->assertDontSee('Adjustment', false);
    }

    public function test_promo_consistent_booking_shows_no_adjustment_row(): void
    {
        PromoCode::create([
            'code' => 'ADJ10',
            'type' => 'percent',
            'value' => 10,
            'is_active' => true,
        ]);

        $inquiry = $this->confirmedBooking('adjpromo@example.com', ['promo_code' => 'ADJ10']);

        $this->assertNotNull($inquiry->promo_code_id);
        $this->assertTrue((float) $inquiry->discount_amount > 0);

        $this->withSession($this->portalSession($inquiry))
            ->get(route('invoice.show', $inquiry))
            ->assertOk()
            ->assertSee('Promo Discount', false)
            ->assertDontSee('Adjustment', false);
    }

    public function test_drifted_total_shows_exact_positive_adjustment(): void
    {
        $inquiry = $this->confirmedBooking('adjdrift@example.com');
        $inquiry->update(['total_amount' => Money::add((string) $inquiry->total_amount, '1.00')]);

        $this->withSession($this->portalSession($inquiry->refresh()))
            ->get(route('invoice.show', $inquiry))
            ->assertOk()
            ->assertSee('Adjustment', false)
            ->assertSee('₱1.00', false);
    }

    /**
     * Reachable 2-decimal adjustment goldens, captured: the legacy
     * expression — value and abs() >= 0.01 visibility alike — agrees with
     * the Money expression on this runtime, including negatives and the
     * ±0.01 boundary. These stay green through the seam migration.
     *
     * @dataProvider adjustmentCases
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('adjustmentCases')]
    public function test_legacy_adjustment_agrees_with_money(
        string $total,
        string $subtotal,
        string $discount,
        string $expectedAdjustment,
        bool $expectedShow
    ): void {
        $legacy = $this->legacyAdjustment($total, $subtotal, $discount);
        $money = $this->moneyAdjustment($total, $subtotal, $discount);

        $this->assertSame($expectedAdjustment, $legacy['adjustment']);
        $this->assertSame($expectedShow, $legacy['show']);
        $this->assertSame($legacy, $money);
    }

    public static function adjustmentCases(): array
    {
        return [
            'consistent no-promo' => ['6000.00', '6000.00', '0.00', '0.00', false],
            'consistent with promo' => ['6000.00', '8000.00', '2000.00', '0.00', false],
            'consistent fractional' => ['4500.00', '5000.00', '500.00', '0.00', false],
            'positive one centavo' => ['6000.01', '6000.00', '0.00', '0.01', true],
            'negative one centavo' => ['5999.99', '6000.00', '0.00', '-0.01', true],
            'positive peso drift' => ['6001.00', '6000.00', '0.00', '1.00', true],
            'negative peso drift' => ['5999.00', '6000.00', '0.00', '-1.00', true],
            'promo drift' => ['6000.50', '8000.00', '2000.00', '0.50', true],
        ];
    }
}
