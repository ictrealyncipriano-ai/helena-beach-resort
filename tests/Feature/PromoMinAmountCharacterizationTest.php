<?php

namespace Tests\Feature;

use App\Models\PromoCode;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Money Migration — min_amount gate golden characterization for
 * PromoCode::findUsable() line 94.
 *
 * Captures the legacy binary-float comparison outputs BEFORE migrating
 * `(float) $subtotal < (float) $promo->min_amount` to exact
 * `Money::cmp() < 0`. Every absolute golden below was captured from the
 * legacy implementation on this runtime — not inferred. Closes the gap
 * explicitly noted in PromoCodeCharacterizationTest ("Out of scope:
 * findUsable() min_amount comparison"); discountFor() itself is already
 * exact since Phase 4.
 *
 * Captured finding: on this runtime legacy float `<` agrees with
 * `Money::cmp() < 0` across the reachable 2-decimal domain (both legs are
 * decimal:2 strings). The migration therefore preserves behavior while
 * removing the float-wrap of exact values at the eligibility boundary.
 */
class PromoMinAmountCharacterizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Mail::fake();
    }

    private function minPromo(): PromoCode
    {
        return PromoCode::create([
            'code' => 'MIN5K',
            'type' => 'percent',
            'value' => 10,
            'min_amount' => 5000,
            'is_active' => true,
        ]);
    }

    /**
     * Exact replica of the legacy line 94 comparison path.
     */
    private function legacyReject(mixed $subtotal, mixed $min): bool
    {
        return (float) $subtotal < (float) $min;
    }

    private function moneyReject(mixed $subtotal, mixed $min): bool
    {
        return Money::cmp((string) $subtotal, (string) $min) < 0;
    }

    public function test_subtotal_below_min_is_rejected(): void
    {
        $this->minPromo();

        $this->assertNull(PromoCode::findUsable('MIN5K', '4999.99'));
    }

    public function test_subtotal_equal_to_min_is_accepted(): void
    {
        $promo = $this->minPromo();

        $found = PromoCode::findUsable('min5k', '5000.00');

        $this->assertNotNull($found);
        $this->assertSame($promo->id, $found->id);
    }

    public function test_subtotal_above_min_is_accepted(): void
    {
        $this->minPromo();

        $this->assertNotNull(PromoCode::findUsable('MIN5K', '5000.01'));
    }

    /**
     * Reachable 2-decimal gate goldens, captured: legacy float `<`
     * agrees with Money::cmp() < 0 on this runtime. These stay green
     * through the gate migration.
     *
     * @dataProvider gateCases
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('gateCases')]
    public function test_legacy_gate_agrees_with_money(string $subtotal, string $min, bool $expectedReject): void
    {
        $this->assertSame($expectedReject, $this->legacyReject($subtotal, $min));
        $this->assertSame($expectedReject, $this->moneyReject($subtotal, $min));
    }

    public static function gateCases(): array
    {
        return [
            'one centavo short' => ['4999.99', '5000.00', true],
            'exact boundary accepted' => ['5000.00', '5000.00', false],
            'one centavo over' => ['5000.01', '5000.00', false],
            'well below' => ['1000.00', '5000.00', true],
            'zero of zero' => ['0.00', '0.00', false],
        ];
    }
}
