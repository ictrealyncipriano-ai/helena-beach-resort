<?php

namespace Tests\Unit\Services;

use App\Models\Cottage;
use App\Models\PromoCode;
use App\Services\PricingService;
use Carbon\Carbon;
use Tests\TestCase;

/**
 * Money Migration — Phase 3 golden characterization for PricingService.
 *
 * Pure-unit (no database): in-memory Cottage/PromoCode models pin the
 * booking/pricing semantics that the Money migration must preserve.
 *
 * Boundary: nightlyTotal()/applyDiscount() totals are exact string
 * arithmetic. PromoCode::discountFor() stays the frozen discount boundary —
 * its float percent internals are explicitly deferred to Phase 4, so percent
 * cases below pin downstream exactness only, not exact percentage math.
 */
class PricingServiceCharacterizationTest extends TestCase
{
    private PricingService $pricing;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pricing = new PricingService();
    }

    private function cottage(array $overrides = []): Cottage
    {
        return new Cottage(array_merge([
            'name' => 'Test Cottage',
            'rate_daytour' => 1000,
            'rate_overnight' => 1500,
        ], $overrides));
    }

    public function test_mixed_peak_three_night_sum(): void
    {
        $cottage = $this->cottage([
            'rate_overnight' => 1500,
            'peak_start' => '2026-11-11',
            'peak_end' => '2026-11-11',
            'peak_rate_overnight' => 1800,
        ]);

        $total = $this->pricing->nightlyTotal(
            $cottage,
            Carbon::parse('2026-11-10'),
            Carbon::parse('2026-11-13')
        );

        // Nov 10 (1500) + Nov 11 peak (1800) + Nov 12 (1500).
        $this->assertSame('4800.00', $total);
    }

    public function test_small_rate_accumulation_avoids_float_drift(): void
    {
        $cottage = $this->cottage(['rate_overnight' => 0.10]);

        $total = $this->pricing->nightlyTotal(
            $cottage,
            Carbon::parse('2026-11-10'),
            Carbon::parse('2026-11-13')
        );

        $this->assertSame('0.30', $total);
    }

    public function test_same_day_checkout_counts_one_night(): void
    {
        $cottage = $this->cottage(['rate_overnight' => 1500]);
        $date = Carbon::parse('2026-11-10');

        $this->assertSame('1500.00', $this->pricing->nightlyTotal($cottage, $date, $date));
    }

    public function test_breakdown_returns_one_rate_per_night(): void
    {
        $cottage = $this->cottage([
            'rate_overnight' => 1500,
            'peak_start' => '2026-11-11',
            'peak_end' => '2026-11-11',
            'peak_rate_overnight' => 1800,
        ]);

        $breakdown = $this->pricing->nightlyBreakdown(
            $cottage,
            Carbon::parse('2026-11-10'),
            Carbon::parse('2026-11-13')
        );

        $this->assertCount(3, $breakdown);
        $this->assertSame('1500.00', $breakdown[0]['rate']);
        $this->assertSame('1800.00', $breakdown[1]['rate']);
        $this->assertSame('1500.00', $breakdown[2]['rate']);
    }

    public function test_apply_fixed_discount(): void
    {
        $promo = new PromoCode(['code' => 'FIXED10', 'type' => 'fixed', 'value' => 10]);

        $result = $this->pricing->applyDiscount('100.00', $promo);

        $this->assertSame('10.00', $result['discount']);
        $this->assertSame('90.00', $result['total']);
        $this->assertSame($promo, $result['promo']);
    }

    public function test_apply_percent_discount_downstream_exact(): void
    {
        // discountFor() internals stay float until Phase 4; this pins the
        // downstream total: 10% of 333.33 -> 33.33 discount -> 300.00 total.
        $promo = new PromoCode(['code' => 'PCT10', 'type' => 'percent', 'value' => 10]);

        $result = $this->pricing->applyDiscount('333.33', $promo);

        $this->assertSame('33.33', $result['discount']);
        $this->assertSame('300.00', $result['total']);
    }

    public function test_discount_clamps_at_zero_when_exceeding_subtotal(): void
    {
        $promo = new PromoCode(['code' => 'BIG', 'type' => 'fixed', 'value' => 5000]);

        $result = $this->pricing->applyDiscount('100.00', $promo);

        $this->assertSame('100.00', $result['discount']);
        $this->assertSame('0.00', $result['total']);
    }

    public function test_subtotal_with_extra_decimals_normalizes_in_total(): void
    {
        $promo = new PromoCode(['code' => 'FIXED1', 'type' => 'fixed', 'value' => 1]);

        $result = $this->pricing->applyDiscount('10.005', $promo);

        // '10.005' normalizes to '10.01' via Money half-up; discount '1.00'.
        $this->assertSame('1.00', $result['discount']);
        $this->assertSame('9.01', $result['total']);
    }

    public function test_null_passthroughs_preserved(): void
    {
        $this->assertSame(
            ['total' => null, 'discount' => '0.00', 'promo' => null],
            $this->pricing->applyDiscount(null, null)
        );

        $result = $this->pricing->applyDiscount('5000.00', null);

        $this->assertSame('5000.00', $result['total']);
        $this->assertSame('0.00', $result['discount']);
        $this->assertNull($result['promo']);
    }

    public function test_calculate_total_nulls_and_day_tour(): void
    {
        $cottage = $this->cottage(['rate_daytour' => 1000]);

        $this->assertNull($this->pricing->calculateTotal($cottage, null, null, 'overnight'));
        $this->assertNull($this->pricing->calculateTotal($cottage, null, null, 'day_tour'));
        $this->assertSame(
            '1000.00',
            $this->pricing->calculateTotal($cottage, Carbon::parse('2026-11-10'), null, 'day_tour')
        );
    }
}
