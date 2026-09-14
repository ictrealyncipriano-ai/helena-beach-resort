<?php

namespace Tests\Feature\Payments;

use App\Http\Controllers\Admin\DashboardController;
use App\Models\Cottage;
use App\Models\Inquiry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Dashboard revenue must not assume deposit_paid_at: a fully-paid
 * non-deposit booking counts via fully_paid_at, and each booking is
 * attributed to exactly one month (no deposit+balance double-count).
 */
class DashboardRevenueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Cache::flush();
    }

    private function booking(array $overrides): Inquiry
    {
        return Inquiry::create(array_merge([
            'name' => 'Guest',
            'email' => uniqid('dashrev').'@example.com',
            'phone' => '09170000000',
            'booking_type' => 'overnight',
            'cottage_id' => Cottage::first()->id,
            'check_in' => '2026-09-01',
            'check_out' => '2026-09-03',
            'pax' => 2,
            'status' => Inquiry::STATUS_CONFIRMED,
            'source' => 'website',
        ], $overrides));
    }

    public function test_dashboard_counts_fully_paid_non_deposit_booking(): void
    {
        $this->booking([
            'total_amount' => '3000.00',
            'amount_paid' => '3000.00',
            'deposit_amount' => null,
            'deposit_paid_at' => null,
            'fully_paid_at' => now(),
        ]);
        $this->booking([
            'total_amount' => '5000.00',
            'deposit_amount' => '1000.00',
            'amount_paid' => '1000.00',
            'deposit_paid_at' => now(),
            'fully_paid_at' => null,
        ]);

        $admin = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();

        $stats = Cache::get(DashboardController::cacheKey());
        $this->assertNotNull($stats);
        $this->assertSame(2, $stats['paidThisMonth']);
        $this->assertEquals(4000, (float) $stats['revenueThisMonth']);
        $this->assertEquals(4000, (float) $stats['revenueData']->sum());
        $this->assertTrue(isset($stats['revenueData'][now()->format('Y-m')]));
    }
}
