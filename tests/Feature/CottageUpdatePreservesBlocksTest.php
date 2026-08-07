<?php

namespace Tests\Feature;

use App\Models\Cottage;
use App\Models\Inquiry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CottageUpdatePreservesBlocksTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    private function cottage(array $overrides = []): Cottage
    {
        return Cottage::create(array_merge([
            'name' => 'Beach Villa',
            'slug' => 'beach-villa',
            'rate_daytour' => 1500,
            'rate_overnight' => 3000,
            'is_available' => true,
        ], $overrides));
    }

    public function test_cottage_update_preserves_inquiry_held_blocks(): void
    {
        $cottage = $this->cottage();

        $inquiry = Inquiry::create([
            'reference_code' => Inquiry::generateReferenceCode(),
            'name' => 'Guest',
            'email' => 'guest@example.com',
            'check_in' => '2026-09-01',
            'check_out' => '2026-09-03',
            'cottage_id' => $cottage->id,
            'status' => 'pending',
            'source' => 'website',
        ]);
        $inquiry->reserveBlocks();

        $admin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($admin)
            ->put(route('admin.cottages.update', $cottage), [
                'name' => 'Beach Villa',
                'slug' => 'beach-villa',
                'rate_daytour' => 1800,
                'rate_overnight' => 3600,
                'is_available' => 1,
                'date_blocks' => [
                    ['date' => '2026-12-25', 'reason' => 'Closed for Christmas'],
                ],
            ])
            ->assertRedirect();

        // The pending hold on the inquiry's dates survives the cottage update.
        foreach (['2026-09-01', '2026-09-02', '2026-09-03'] as $date) {
            $this->assertDatabaseHas('cottage_date_blocks', [
                'cottage_id' => $cottage->id,
                'date' => $date,
                'reason' => "Pending: {$inquiry->reference_code}",
            ]);
        }

        // The admin's own block is still stored as well.
        $this->assertDatabaseHas('cottage_date_blocks', [
            'cottage_id' => $cottage->id,
            'date' => '2026-12-25',
            'reason' => 'Closed for Christmas',
        ]);
    }

    public function test_cottage_update_refreshes_admin_blocks(): void
    {
        $cottage = $this->cottage(['name' => 'Sunset', 'slug' => 'sunset']);

        $cottage->dateBlocks()->create(['date' => '2026-12-24', 'reason' => 'Maintenance']);

        $admin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($admin)
            ->put(route('admin.cottages.update', $cottage), [
                'name' => 'Sunset',
                'slug' => 'sunset',
                'rate_daytour' => 1000,
                'rate_overnight' => 2000,
                'is_available' => 1,
                'date_blocks' => [
                    ['date' => '2026-12-24', 'reason' => 'Closed'],
                    ['date' => '2026-12-25', 'reason' => 'Closed'],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('cottage_date_blocks', [
            'cottage_id' => $cottage->id,
            'date' => '2026-12-24',
            'reason' => 'Closed',
        ]);
        $this->assertDatabaseMissing('cottage_date_blocks', [
            'cottage_id' => $cottage->id,
            'date' => '2026-12-24',
            'reason' => 'Maintenance',
        ]);
        $this->assertDatabaseHas('cottage_date_blocks', [
            'cottage_id' => $cottage->id,
            'date' => '2026-12-25',
            'reason' => 'Closed',
        ]);
    }
}
