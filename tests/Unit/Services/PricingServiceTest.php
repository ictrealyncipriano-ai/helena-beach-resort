<?php

namespace Tests\Unit\Services;

use App\Models\Cottage;
use App\Models\PromoCode;
use App\Services\PricingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PricingServiceTest extends TestCase
{
    use RefreshDatabase;

    private PricingService $pricing;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pricing = new PricingService();
        $this->seed();
    }

    public function test_day_tour_returns_single_rate(): void
    {
        $cottage = Cottage::first();
        $date = Carbon::parse('2026-09-15');

        $result = $this->pricing->calculateTotal($cottage, $date, null, 'day_tour');

        $this->assertNotNull($result);
        $this->assertEquals($cottage->rateFor($date, 'day_tour'), $result);
    }

    public function test_overnight_sums_per_night_rates(): void
    {
        $cottage = Cottage::first();
        $checkIn = Carbon::parse('2026-09-15');
        $checkOut = Carbon::parse('2026-09-18');

        $result = $this->pricing->calculateTotal($cottage, $checkIn, $checkOut, 'overnight');

        $expected = number_format(
            (float) $cottage->rateFor($checkIn, 'overnight')
            + (float) $cottage->rateFor($checkIn->copy()->addDay(), 'overnight')
            + (float) $cottage->rateFor($checkIn->copy()->addDays(2), 'overnight'),
            2, '.', ''
        );

        $this->assertEquals($expected, $result);
    }

    public function test_nightly_breakdown_returns_per_night_details(): void
    {
        $cottage = Cottage::first();
        $checkIn = Carbon::parse('2026-09-15');
        $checkOut = Carbon::parse('2026-09-17');

        $breakdown = $this->pricing->nightlyBreakdown($cottage, $checkIn, $checkOut);

        $this->assertCount(2, $breakdown);
        $this->assertEquals('Sep 15, 2026', $breakdown[0]['date']);
        $this->assertEquals('Sep 16, 2026', $breakdown[1]['date']);
        $this->assertArrayHasKey('rate', $breakdown[0]);
    }

    public function test_apply_discount_with_percent_promo(): void
    {
        $promo = PromoCode::where('type', 'percent')->first();

        if (! $promo) {
            $this->markTestSkipped('No percent promo code seeded.');
        }

        $result = $this->pricing->applyDiscount('10000.00', $promo);

        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('discount', $result);
        $this->assertArrayHasKey('promo', $result);
        $this->assertEquals($promo->id, $result['promo']->id);
        $this->assertNotEquals('10000.00', $result['total']);
    }

    public function test_apply_discount_without_promo_passes_through(): void
    {
        $result = $this->pricing->applyDiscount('5000.00', null);

        $this->assertEquals('5000.00', $result['total']);
        $this->assertEquals('0.00', $result['discount']);
        $this->assertNull($result['promo']);
    }

    public function test_apply_discount_with_null_subtotal_returns_null(): void
    {
        $result = $this->pricing->applyDiscount(null, null);

        $this->assertNull($result['total']);
        $this->assertEquals('0.00', $result['discount']);
    }

    public function test_calculate_total_returns_null_for_missing_dates(): void
    {
        $cottage = Cottage::first();

        $this->assertNull($this->pricing->calculateTotal($cottage, null, null, 'overnight'));
        $this->assertNull($this->pricing->calculateTotal($cottage, null, null, 'day_tour'));
    }

    public function test_nightly_total_handles_single_night(): void
    {
        $cottage = Cottage::first();
        $date = Carbon::parse('2026-09-15');

        $result = $this->pricing->nightlyTotal($cottage, $date, $date->copy()->addDay());

        $this->assertEquals($cottage->rateFor($date, 'overnight'), $result);
    }

    public function test_cottage_rates_map_returns_all_cottages(): void
    {
        $map = Cottage::ratesMap();

        $this->assertGreaterThan(0, $map->count());

        $first = $map->first();
        $this->assertArrayHasKey('name', $first);
        $this->assertArrayHasKey('day_tour', $first);
        $this->assertArrayHasKey('overnight', $first);
        $this->assertArrayHasKey('peak_day_tour', $first);
        $this->assertArrayHasKey('peak_start', $first);
        $this->assertArrayHasKey('capacity', $first);
    }

    public function test_cottage_rates_map_with_collection(): void
    {
        $cottages = Cottage::where('is_available', true)->take(2)->get();
        $map = Cottage::ratesMap($cottages);

        $this->assertCount(2, $map);
    }
}
