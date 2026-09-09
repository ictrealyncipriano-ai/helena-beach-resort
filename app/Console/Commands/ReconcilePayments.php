<?php

namespace App\Console\Commands;

use App\Models\Inquiry;
use App\Services\PaymentReconciliationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * WP-4: conservative scheduled reconciliation.
 *
 * 1. Cancelled/expired bookings still pointing at a checkout session are
 *    re-checked (report-only: the shared credit routine never records
 *    against a non-confirmed booking, so a late payment surfaces as
 *    `ignored` here and is queued for the WP-6 workflow).
 * 2. The WP-3 stale-pending sweep runs (clear/record/skip/review).
 *
 * Ambiguous payments are logged, never blindly mutated. Refund-state
 * recovery arrives with WP-7's refund states; until then this command
 * covers pendings + cancelled-with-session discrepancies.
 */
class ReconcilePayments extends Command
{
    protected $signature = 'payments:reconcile
        {--dry-run : Report what would be evaluated without calling PayMongo or writing}
        {--ttl= : Stale hours for session-backed pendings (defaults to the service constant)}
        {--legacy-ttl= : Stale hours for session-less pendings (defaults to the service constant)}';

    protected $description = 'Reconcile payment pendings against PayMongo and clear abandoned checkouts (conservative)';

    public function handle(PaymentReconciliationService $reconciliation): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $ttl = $this->option('ttl') !== null ? max(1, (int) $this->option('ttl')) : null;
        $legacyTtl = $this->option('legacy-ttl') !== null ? max(1, (int) $this->option('legacy-ttl')) : null;

        if ($dryRun) {
            return $this->reportDryRun();
        }

        // 1. Cancelled/expired bookings with an outstanding session pointer.
        $flagged = 0;
        Inquiry::whereIn('status', [Inquiry::STATUS_CANCELLED, Inquiry::STATUS_EXPIRED])
            ->whereNotNull('paymongo_session_id')
            ->where('updated_at', '>=', now()->subDays(7))
            ->chunkById(100, function ($inquiries) use ($reconciliation, &$flagged) {
                foreach ($inquiries as $inquiry) {
                    $result = $reconciliation->reconcileByCheckoutId($inquiry->paymongo_session_id);
                    $needsAttention = in_array($result['outcome'], ['ignored', 'mismatch', 'unmatched', 'error'], true)
                        || (($result['outcome'] ?? null) === 'late_payment' && ($result['refund'] ?? null) !== 'refunded');

                    if ($needsAttention) {
                        $flagged++;
                        $this->warn("  ATTENTION {$inquiry->reference_code} ({$inquiry->status}): {$result['outcome']}");
                        Log::warning('payments:reconcile flagged non-confirmed booking with session', [
                            'inquiry_id' => $inquiry->id,
                            'status' => $inquiry->status,
                            'outcome' => $result['outcome'],
                        ]);
                    }
                }
            });

        // 2. Stale-pending sweep (WP-3 rules).
        $stats = $reconciliation->sweepStalePendings($ttl, $legacyTtl);

        $this->newLine();
        $this->info("Done: {$stats['recorded']} recovered, {$stats['cleared']} abandoned cleared, "
            ."{$stats['skipped']} fresh skipped, {$stats['needs_review']} need review, {$flagged} non-confirmed flagged.");

        return self::SUCCESS;
    }

    private function reportDryRun(): int
    {
        $withPending = Inquiry::whereNotNull('payment_pending_amount')->count();
        $withSession = Inquiry::whereNotNull('payment_pending_amount')
            ->whereNotNull('paymongo_session_id')->count();
        $nonConfirmed = Inquiry::whereNotNull('payment_pending_amount')
            ->where('status', '!=', Inquiry::STATUS_CONFIRMED)->count();
        $flaggedSessions = Inquiry::whereIn('status', [Inquiry::STATUS_CANCELLED, Inquiry::STATUS_EXPIRED])
            ->whereNotNull('paymongo_session_id')
            ->where('updated_at', '>=', now()->subDays(7))->count();

        $this->info('payments:reconcile --dry-run (no API calls, no writes)');
        $this->line("  pendings held: {$withPending} ({$withSession} session-backed, {$nonConfirmed} non-confirmed)");
        $this->line("  non-confirmed bookings with recent sessions to re-check: {$flaggedSessions}");

        return self::SUCCESS;
    }
}
