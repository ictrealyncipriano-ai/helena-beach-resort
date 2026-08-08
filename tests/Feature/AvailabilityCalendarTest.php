<?php

namespace Tests\Feature;

use App\Models\Cottage;
use App\Models\CottageDateBlock;
use App\Models\Inquiry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AvailabilityCalendarTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'super_admin']);
    }

    private function cottage(): Cottage
    {
        return Cottage::create([
            'name' => 'Beach Villa',
            'capacity' => 8,
            'rate_daytour' => 1500,
            'rate_overnight' => 3000,
            'is_available' => true,
            'sort_order' => 1,
        ]);
    }

    public function test_calendar_renders_for_current_month(): void
    {
        $this->cottage();

        $this->actingAs($this->admin())
            ->get(route('admin.availability'))
            ->assertOk()
            ->assertSee(now()->format('F Y'), false)
            ->assertSee('Beach Villa');
    }

    public function test_calendar_marks_a_booked_date(): void
    {
        $cottage = $this->cottage();
        $date = now()->startOfMonth()->addDays(5);

        CottageDateBlock::create([
            'cottage_id' => $cottage->id,
            'date' => $date->toDateString(),
            'reason' => 'Booked: HB-TEST123',
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.availability'))
            ->assertOk()
            ->assertSee($date->format('D j'), false)
            ->assertSee('HB-TEST123');
    }

    public function test_calendar_supports_month_navigation(): void
    {
        $prev = now()->subMonth()->format('Y-m');

        $this->actingAs($this->admin())
            ->get(route('admin.availability', ['month' => $prev]))
            ->assertOk()
            ->assertSee(now()->subMonth()->format('F Y'), false);
    }

    public function test_invalid_month_falls_back_to_current(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.availability', ['month' => 'not-a-month']))
            ->assertOk()
            ->assertSee(now()->format('F Y'), false);
    }

    public function test_manual_block_shows_inquiry_reference_when_present(): void
    {
        $cottage = $this->cottage();
        $date = now()->startOfMonth()->addDays(10);

        $inquiry = Inquiry::create([
            'reference_code' => 'HB-MANUAL01',
            'name' => 'Pending Guest',
            'email' => 'pending@example.com',
            'cottage_id' => $cottage->id,
            'check_in' => $date->toDateString(),
            'check_out' => $date->toDateString(),
            'status' => 'pending',
            'source' => 'website',
        ]);
        $inquiry->reserveBlocks();

        $this->actingAs($this->admin())
            ->get(route('admin.availability'))
            ->assertOk()
            ->assertSee('HB-MANUAL01');
    }
}
