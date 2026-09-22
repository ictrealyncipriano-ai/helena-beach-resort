<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Slice 6 — portable query indexes (characterization-first).
 *
 * Each hot query path below must have a matching index on every database
 * driver (the Phase-4 raw-SQL indexes are pgsql-only). Fails pre-fix,
 * passes once the Slice 6 migration lands. Runs against SQLite, proving
 * the migration is portable.
 */
class Slice6IndexCoverageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, string[]>
     */
    private function indexes(): array
    {
        $columns = [];

        foreach (Schema::getIndexes('inquiries') as $index) {
            $columns[$index['name']] = $index['columns'];
        }

        return $columns;
    }

    private function assertHasIndexOn(array $expectedColumns): void
    {
        foreach ($this->indexes() as $name => $columns) {
            if ($columns === $expectedColumns) {
                $this->assertTrue(true, "index {$name} covers [".implode(', ', $columns).']');

                return;
            }
        }

        $this->fail('No index covers ['.implode(', ', $expectedColumns).']');
    }

    public function test_sweep_scan_is_indexed(): void
    {
        // PaymentReconciliationService stale-pending sweep.
        $this->assertHasIndexOn(['status', 'payment_pending_at']);
    }

    public function test_failed_refund_scan_is_indexed(): void
    {
        // RetryRefunds: refund_status = failed.
        $this->assertHasIndexOn(['refund_status']);
    }

    public function test_expiry_warning_scan_is_indexed(): void
    {
        // ReleaseExpiredReservations: pending + unwarned window.
        $this->assertHasIndexOn(['status', 'expiry_warned_at']);
    }

    public function test_proof_queue_scan_is_indexed(): void
    {
        // Admin payment-proof review queue.
        $this->assertHasIndexOn(['payment_proof_status']);
    }

    public function test_session_reconcile_scan_is_indexed_portably(): void
    {
        // ReconcilePayments: whereNotNull(paymongo_session_id) on all drivers.
        $this->assertHasIndexOn(['paymongo_session_id']);
    }
}
