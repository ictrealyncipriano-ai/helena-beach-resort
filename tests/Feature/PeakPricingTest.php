<?php

namespace Tests\Feature;

use App\Models\Cottage;
use App\Models\Inquiry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PeakPricingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Mail::fake();
    }

    private function peakCottage(array $overrides = []): Cottage
    {
        return Cottage::create(array_merge([
            'name' => 'Peak Villa',
            'description' => 'Peak aware',
            'rate_daytour' => 1000,
            'rate_overnight' => 2000,
            'peak_start' => '2026-12-20',
            'peak_end' => '2027-01-05',
            'peak_rate_daytour' => 1500,
            'peak_rate_overnight' => 3000,
            'is_available' => true,
        ], $overrides));
    }

    private function book(Cottage $cottage, array $overrides = [])
    {
        return $this->post('/book', array_merge([
            'name' => 'Peak Guest',
            'email' => 'peak@example.com',
            'booking_type' => 'overnight',
            'cottage_id' => $cottage->id,
            'check_in' => '2026-12-24',
            'check_out' => '2026-12-26',
            'pax' => 2,
        ], $overrides));
    }

    public function test_rate_for_peak_date_applies_peak_rate(): void
    {
        $cottage = $this->peakCottage();

        $this->assertSame('3000.00', $cottage->rateFor(\Carbon\Carbon::parse('2026-12-24')));
        $this->assertSame('3000.00', $cottage->rateFor(\Carbon\Carbon::parse('2027-01-02')));
        $this->assertSame('2000.00', $cottage->rateFor(\Carbon\Carbon::parse('2026-11-15')));
    }

    public function test_rate_for_cross_year_window_is_detected(): void
    {
        $cottage = $this->peakCottage();

        $this->assertTrue($cottage->isPeakDate(\Carbon\Carbon::parse('2026-12-31')));
        $this->assertTrue($cottage->isPeakDate(\Carbon\Carbon::parse('2027-01-04')));
        $this->assertFalse($cottage->isPeakDate(\Carbon\Carbon::parse('2027-01-06')));
        $this->assertFalse($cottage->isPeakDate(\Carbon\Carbon::parse('2026-12-19')));
    }

    public function test_peak_dates_are_ignored_when_peak_rate_not_set(): void
    {
        $cottage = $this->peakCottage(['peak_rate_daytour' => null, 'peak_rate_overnight' => null]);

        $this->assertSame('2000.00', $cottage->rateFor(\Carbon\Carbon::parse('2026-12-24')));
        $this->assertSame('1000.00', $cottage->rateFor(\Carbon\Carbon::parse('2026-12-24'), 'day_tour'));
    }

    public function test_overnight_booking_total_mixes_peak_and_regular_nights(): void
    {
        $cottage = $this->peakCottage();

        // Jan 4 (peak) + Jan 5 (peak) + Jan 6 (regular) = 3000+3000+2000
        $this->book($cottage, ['check_in' => '2027-01-04', 'check_out' => '2027-01-07']);

        $inquiry = Inquiry::where('email', 'peak@example.com')->first();
        $this->assertSame('8000.00', (string) $inquiry->total_amount);
    }

    public function test_day_tour_booking_uses_peak_rate(): void
    {
        $cottage = $this->peakCottage();

        $this->book($cottage, [
            'booking_type' => 'day_tour',
            'check_in' => '2026-12-24',
            'check_out' => null,
        ]);

        $inquiry = Inquiry::where('email', 'peak@example.com')->first();
        $this->assertSame('1500.00', (string) $inquiry->total_amount);
    }

    public function test_regular_booking_keeps_standard_rates(): void
    {
        $cottage = $this->peakCottage();

        $this->book($cottage, ['check_in' => '2026-11-10', 'check_out' => '2026-11-12']);

        $inquiry = Inquiry::where('email', 'peak@example.com')->first();
        $this->assertSame('4000.00', (string) $inquiry->total_amount);
    }

    public function test_admin_can_save_peak_pricing(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($admin)
            ->post(route('admin.cottages.store'), [
                'name' => 'Peak Cottage',
                'rate_daytour' => 1000,
                'rate_overnight' => 2000,
                'peak_start' => '2026-12-20',
                'peak_end' => '2027-01-05',
                'peak_rate_daytour' => 1500,
                'peak_rate_overnight' => 3000,
                'is_available' => 1,
            ])
            ->assertRedirect(route('admin.cottages.index'));

        $cottage = Cottage::where('name', 'Peak Cottage')->first();
        $this->assertSame('2026-12-20', $cottage->peak_start->format('Y-m-d'));
        $this->assertSame('2027-01-05', $cottage->peak_end->format('Y-m-d'));
        $this->assertSame('1500.00', (string) $cottage->peak_rate_daytour);
        $this->assertSame('3000.00', (string) $cottage->peak_rate_overnight);
    }

    public function test_has_peak_pricing_true_when_configured(): void
    {
        $cottage = $this->peakCottage();
        $this->assertTrue($cottage->hasPeakPricing());
    }

    public function test_has_peak_pricing_false_when_no_peak_rates(): void
    {
        $cottage = $this->peakCottage(['peak_rate_daytour' => null, 'peak_rate_overnight' => null]);
        $this->assertFalse($cottage->hasPeakPricing());
    }

    public function test_has_peak_pricing_false_when_only_start_set(): void
    {
        $cottage = $this->peakCottage(['peak_end' => null, 'peak_rate_daytour' => 1500]);
        $this->assertFalse($cottage->hasPeakPricing());
    }

    public function test_single_day_peak_window(): void
    {
        $cottage = $this->peakCottage([
            'peak_start' => '2026-12-25',
            'peak_end' => '2026-12-25',
        ]);

        $this->assertTrue($cottage->isPeakDate(\Carbon\Carbon::parse('2026-12-25')));
        $this->assertFalse($cottage->isPeakDate(\Carbon\Carbon::parse('2026-12-24')));
    }

    public function test_admin_validation_rejects_start_without_end(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($admin)
            ->post(route('admin.cottages.store'), [
                'name' => 'Bad Cottage',
                'rate_overnight' => 2000,
                'peak_start' => '2026-12-20',
                'peak_rate_overnight' => 3000,
                'is_available' => 1,
            ])
            ->assertSessionHasErrors('peak_end');
    }

    public function test_admin_validation_rejects_end_without_start(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($admin)
            ->post(route('admin.cottages.store'), [
                'name' => 'Bad Cottage',
                'rate_overnight' => 2000,
                'peak_end' => '2027-01-05',
                'peak_rate_overnight' => 3000,
                'is_available' => 1,
            ])
            ->assertSessionHasErrors('peak_start');
    }

    public function test_admin_validation_rejects_zero_peak_rates(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($admin)
            ->post(route('admin.cottages.store'), [
                'name' => 'Bad Cottage',
                'rate_overnight' => 2000,
                'peak_start' => '2026-12-20',
                'peak_end' => '2027-01-05',
                'peak_rate_daytour' => 0,
                'peak_rate_overnight' => 0,
                'is_available' => 1,
            ])
            ->assertSessionHasErrors('peak_rate_daytour');
    }

    public function test_cottage_without_peak_config_uses_base_rates(): void
    {
        $cottage = Cottage::create([
            'name' => 'Base Villa',
            'rate_daytour' => 800,
            'rate_overnight' => 1600,
            'is_available' => true,
        ]);

        $this->assertSame('1600.00', $cottage->rateFor(\Carbon\Carbon::parse('2026-12-25')));
        $this->assertSame('800.00', $cottage->rateFor(\Carbon\Carbon::parse('2026-12-25'), 'day_tour'));
        $this->assertFalse($cottage->hasPeakPricing());
    }
}