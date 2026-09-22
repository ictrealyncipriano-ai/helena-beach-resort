<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * Slice 8 — queue + cron correctness (characterization-first).
 *
 * Desired end state (must FAIL pre-fix unless noted as guard):
 *  - The database queue dispatches after commit, so queued mail can never
 *    fire for a rolled-back booking.
 *  - The cron trigger is a named route, referenceable via route().
 * Guards (pass pre- and post-fix, pinning Vercel compatibility and the
 * fail-closed bearer auth through the change):
 *  - GET and POST with a valid bearer both trigger the job.
 *  - Missing/invalid bearer is 401 on both verbs.
 */
class QueueCronCharacterizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_database_queue_dispatches_after_commit(): void
    {
        $this->assertTrue(Config::get('queue.connections.database.after_commit'));
    }

    public function test_cron_trigger_is_a_named_route(): void
    {
        $this->assertSame(url('/cron/reservations'), route('cron.reservations'));
    }

    public function test_cron_trigger_accepts_get_and_post_with_valid_bearer(): void
    {
        Config::set('cron.secret', 'test-cron-secret');

        $this->withHeaders(['Authorization' => 'Bearer test-cron-secret'])
            ->get('/cron/reservations')
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->withHeaders(['Authorization' => 'Bearer test-cron-secret'])
            ->post('/cron/reservations')
            ->assertOk()
            ->assertJson(['ok' => true]);
    }

    public function test_cron_trigger_rejects_missing_bearer_on_both_verbs(): void
    {
        Config::set('cron.secret', 'test-cron-secret');

        $this->get('/cron/reservations')->assertStatus(401);
        $this->post('/cron/reservations')->assertStatus(401);
    }
}
