<?php

namespace Tests\Feature;

use App\Models\Cottage;
use App\Models\Inquiry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CronControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function createStalePendingInquiry(): Inquiry
    {
        $cottage = Cottage::first();

        $inquiry = Inquiry::create([
            'name' => 'Stale Guest',
            'email' => 'stale-cron@example.com',
            'check_in' => '2026-09-01',
            'check_out' => '2026-09-03',
            'cottage_id' => $cottage->id,
            'source' => 'website',
        ]);
        $inquiry->created_at = now()->subHours(72)->toDateTimeString();
        $inquiry->save();

        $inquiry->reserveBlocks();

        return $inquiry;
    }

    public function test_cron_endpoint_requires_bearer_token(): void
    {
        $this->post('/cron/reservations')->assertStatus(401);

        $this->withHeader('Authorization', 'Bearer wrong-secret')
            ->post('/cron/reservations')
            ->assertStatus(401);
    }

    public function test_cron_endpoint_expires_stale_pending_reservations(): void
    {
        $inquiry = $this->createStalePendingInquiry();

        $this->withHeader('Authorization', 'Bearer test-cron-secret')
            ->post('/cron/reservations')
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertDatabaseHas('inquiries', [
            'id' => $inquiry->id,
            'status' => 'expired',
        ]);
        $this->assertDatabaseMissing('cottage_date_blocks', [
            'cottage_id' => $inquiry->cottage_id,
            'date' => '2026-09-01',
        ]);
    }

    public function test_migrate_endpoint_is_removed(): void
    {
        $this->post('/cron/migrate')->assertNotFound();

        $this->withHeader('Authorization', 'Bearer wrong-secret')
            ->post('/cron/migrate')
            ->assertNotFound();
    }
}
