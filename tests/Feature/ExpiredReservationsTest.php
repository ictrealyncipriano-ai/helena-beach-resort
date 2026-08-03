<?php

namespace Tests\Feature;

use App\Models\Cottage;
use App\Models\Inquiry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpiredReservationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function createPendingInquiry(string $email, string $createdAt): Inquiry
    {
        $cottage = Cottage::first();

        $inquiry = Inquiry::create([
            'name' => 'Guest',
            'email' => $email,
            'check_in' => '2026-09-01',
            'check_out' => '2026-09-03',
            'cottage_id' => $cottage->id,
            'source' => 'website',
        ]);
        $inquiry->created_at = $createdAt;
        $inquiry->save();

        $inquiry->reserveBlocks();

        return $inquiry;
    }

    public function test_command_expires_stale_pending_and_releases_blocks(): void
    {
        $inquiry = $this->createPendingInquiry('stale@example.com', now()->subHours(72)->toDateTimeString());

        $this->artisan('reservations:release-expired --hours=48')
            ->expectsOutputToContain($inquiry->reference_code)
            ->assertExitCode(0);

        $this->assertDatabaseHas('inquiries', [
            'id' => $inquiry->id,
            'status' => 'expired',
        ]);
        $this->assertDatabaseMissing('cottage_date_blocks', [
            'cottage_id' => $inquiry->cottage_id,
            'date' => '2026-09-01',
        ]);
    }

    public function test_command_leaves_recent_pending_untouched(): void
    {
        $inquiry = $this->createPendingInquiry('fresh@example.com', now()->subHours(2)->toDateTimeString());

        $this->artisan('reservations:release-expired --hours=48')
            ->assertExitCode(0);

        $this->assertDatabaseHas('inquiries', [
            'id' => $inquiry->id,
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('cottage_date_blocks', [
            'cottage_id' => $inquiry->cottage_id,
            'date' => '2026-09-01',
        ]);
    }
}
