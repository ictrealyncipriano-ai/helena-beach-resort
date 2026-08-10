<?php

namespace Tests\Feature;

use App\Models\Cottage;
use App\Models\CottageDateBlock;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AvailabilityEndpointTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function cottage(): Cottage
    {
        return Cottage::first();
    }

    private function check(array $params = [])
    {
        return $this->getJson(route('availability.check', array_merge([
            'cottage_id' => $this->cottage()->id,
            'booking_type' => 'overnight',
            'check_in' => '2026-10-01',
            'check_out' => '2026-10-03',
        ], $params)));
    }

    public function test_free_range_is_available(): void
    {
        $this->check()
            ->assertOk()
            ->assertJson([
                'available' => true,
                'blocked_dates' => [],
            ])
            ->assertJsonPath('cottage.id', $this->cottage()->id)
            ->assertJsonPath('rate.amount', $this->overnightTotal($this->cottage(), '2026-10-01', '2026-10-03'));
    }

    public function test_blocks_inside_range_make_it_unavailable(): void
    {
        CottageDateBlock::create([
            'cottage_id' => $this->cottage()->id,
            'date' => '2026-10-02',
            'reason' => 'Maintenance',
        ]);

        $this->check()
            ->assertOk()
            ->assertJson([
                'available' => false,
                'blocked_dates' => ['2026-10-02'],
            ]);
    }

    public function test_day_tour_checks_only_the_check_in_date(): void
    {
        CottageDateBlock::create([
            'cottage_id' => $this->cottage()->id,
            'date' => '2026-10-02',
            'reason' => 'Maintenance',
        ]);

        $this->check(['booking_type' => 'day_tour', 'check_in' => '2026-10-01', 'check_out' => null])
            ->assertOk()
            ->assertJson(['available' => true]);
    }

    public function test_invalid_parameters_are_rejected(): void
    {
        $cottage = $this->cottage();

        $this->getJson(route('availability.check', [
            'cottage_id' => 999999,
            'booking_type' => 'overnight',
            'check_in' => '2026-10-01',
            'check_out' => '2026-10-03',
        ]))->assertStatus(422);

        $this->check(['check_in' => '2020-01-01'])->assertStatus(422);

        $this->check(['check_out' => '2026-10-01'])->assertStatus(422);

        $this->check(['booking_type' => 'weekly'])->assertStatus(422);

        $this->getJson(route('availability.check'))->assertStatus(422);

        $this->assertTrue($cottage->exists);
    }

    public function test_unavailable_cottage_is_rejected(): void
    {
        $unavailable = Cottage::create([
            'name' => 'Offline Huts',
            'slug' => 'offline-huts',
            'description' => null,
            'capacity' => 4,
            'rate_daytour' => 1000,
            'rate_overnight' => 1500,
            'is_available' => false,
        ]);

        $this->getJson(route('availability.check', [
            'cottage_id' => $unavailable->id,
            'booking_type' => 'day_tour',
            'check_in' => '2026-10-01',
        ]))->assertStatus(422);
    }

    public function test_availability_is_rate_limited_after_60_requests(): void
    {
        for ($i = 0; $i < 60; $i++) {
            $this->check(['check_in' => '2026-11-01', 'check_out' => '2026-11-03'])->assertOk();
        }

        $this->check()->assertStatus(429);
    }

    private function overnightTotal(Cottage $cottage, string $checkIn, string $checkOut): string
    {
        $total = 0;
        $cursor = $checkIn;
        $end = $checkOut;

        while ($cursor < $end) {
            $total += (float) $cottage->rateFor(Carbon::parse($cursor), 'overnight');
            $cursor = date('Y-m-d', strtotime($cursor.' +1 day'));
        }

        return number_format($total, 2, '.', '');
    }
}
