<?php

namespace App\Console\Commands;

use App\Models\Inquiry;
use App\Models\Payment;
use App\Services\PayMongoService;
use App\Services\RefundService;
use Illuminate\Console\Command;

/**
 * WP-7: retry failed online refunds with backoff.
 *
 * Two queues, both bounded (25 rows/run) and backoff-gated:
 *  1. inquiries stuck at refund_status=failed (normal cancel/admin refunds).
 *  2. payments ledger rows stuck at requires_refund (WP-6 late payments).
 *
 * Rows at MAX_ATTEMPTS are left for human review and only reported.
 * Manual settlements (no provider payment id) are never touched.
 */
class RetryRefunds extends Command
{
    protected $signature = 'payments:retry-refunds
        {--dry-run : Report retryable rows without calling PayMongo or writing}
        {--limit=25 : Max rows per queue per run}';

    protected $description = 'Retry failed online refunds with backoff (inquiry + late-payment ledger queues)';

    public function handle(RefundService $refundService, PayMongoService $payMongo): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $limit = max(1, min(100, (int) $this->option('limit')));

        $inquiryStats = $this->retryInquiryQueue($refundService, $payMongo, $limit, $dryRun);
        $ledgerStats = $this->retryLedgerQueue($payMongo, $limit, $dryRun);

        $this->newLine();
        $this->info(sprintf(
            'Done: inquiries retried=%d succeeded=%d still_failing=%d exhausted=%d; late ledger retried=%d refunded=%d still_failing=%d.',
            $inquiryStats['retried'], $inquiryStats['succeeded'], $inquiryStats['failed'],
            $inquiryStats['exhausted'], $ledgerStats['retried'], $ledgerStats['succeeded'], $ledgerStats['failed']
        ));

        return self::SUCCESS;
    }

    /**
     * @return array{retried: int, succeeded: int, failed: int, exhausted: int}
     */
    private function retryInquiryQueue(RefundService $refundService, PayMongoService $payMongo, int $limit, bool $dryRun): array
    {
        $stats = ['retried' => 0, 'succeeded' => 0, 'failed' => 0, 'exhausted' => 0];

        $query = Inquiry::where('refund_status', RefundService::STATUS_FAILED)
            ->whereNotNull('paymongo_payment_id')
            ->orderBy('updated_at');

        if ($dryRun) {
            $count = (clone $query)->limit($limit)->count();
            $this->line("  inquiry queue retryable now: {$count} (showing up to {$limit})");

            return $stats;
        }

        $query->limit($limit)->chunkById(25, function ($inquiries) use ($refundService, $payMongo, &$stats) {
            foreach ($inquiries as $inquiry) {
                if ($inquiry->refund_attempts >= RefundService::MAX_ATTEMPTS) {
                    $stats['exhausted']++;
                    $this->warn("  EXHAUSTED {$inquiry->reference_code} ({$inquiry->refund_attempts} attempts) — needs human review");
                    continue;
                }

                if (! $this->backoffElapsed($inquiry->updated_at, $inquiry->refund_attempts)) {
                    continue;
                }

                if ((float) $inquiry->refundableAmount() <= 0) {
                    continue;
                }

                $stats['retried']++;

                try {
                    $result = $refundService->claimAndProcess($inquiry, $payMongo);
                } catch (\RuntimeException $e) {
                    $stats['failed']++;
                    $this->warn("  FAILED {$inquiry->reference_code}: {$e->getMessage()}");
                    continue;
                }

                if ($result === RefundService::CLAIMED) {
                    $stats['succeeded']++;
                    $this->line("  REFUNDED {$inquiry->reference_code}");
                }
            }
        });

        return $stats;
    }

    /**
     * @return array{retried: int, succeeded: int, failed: int}
     */
    private function retryLedgerQueue(PayMongoService $payMongo, int $limit, bool $dryRun): array
    {
        $stats = ['retried' => 0, 'succeeded' => 0, 'failed' => 0];

        $query = Payment::where('status', Payment::STATUS_REQUIRES_REFUND)
            ->whereNotNull('provider_payment_id')
            ->orderBy('updated_at');

        if ($dryRun) {
            $count = (clone $query)->limit($limit)->count();
            $this->line("  late-ledger queue retryable now: {$count} (showing up to {$limit})");

            return $stats;
        }

        $query->limit($limit)->chunkById(25, function ($rows) use ($payMongo, &$stats) {
            foreach ($rows as $row) {
                $attempts = (int) ($row->metadata['refund_attempts'] ?? 0);

                if ($attempts >= RefundService::MAX_ATTEMPTS) {
                    $this->warn("  EXHAUSTED late payment {$row->provider_payment_id} — needs human review");
                    continue;
                }

                if (! $this->backoffElapsed($row->updated_at, max($attempts, 1))) {
                    continue;
                }

                $stats['retried']++;
                $centavos = $payMongo->toCentavos($row->amount);

                try {
                    $refund = $payMongo->refundPayment(
                        $row->provider_payment_id,
                        $centavos,
                        "late-refund-{$row->inquiry_id}-{$row->provider_payment_id}"
                    );
                } catch (\RuntimeException $e) {
                    $stats['failed']++;
                    $meta = $row->metadata ?? [];
                    $meta['refund_attempts'] = $attempts + 1;
                    $meta['refund_last_error'] = mb_substr($e->getMessage(), 0, 500);
                    $row->update(['metadata' => $meta]);
                    $this->warn("  FAILED late payment {$row->provider_payment_id}: {$e->getMessage()}");
                    continue;
                }

                $refundId = is_array($refund) ? ($refund['id'] ?? null) : null;

                if ($refundId !== null) {
                    Payment::firstOrCreate(
                        ['provider_refund_id' => $refundId],
                        [
                            'inquiry_id' => $row->inquiry_id,
                            'provider' => Payment::PROVIDER_PAYMONGO,
                            'provider_payment_id' => null,
                            'provider_refund_id' => $refundId,
                            'method' => $row->method,
                            'type' => Payment::TYPE_REFUND,
                            'amount' => $row->amount,
                            'currency' => 'PHP',
                            'status' => Payment::STATUS_REFUNDED,
                            'refunded_at' => now(),
                            'metadata' => ['late_payment' => true, 'refunded_payment_id' => $row->provider_payment_id, 'retried' => true],
                        ]
                    );
                }

                $row->update(['status' => Payment::STATUS_REFUNDED, 'refunded_at' => now()]);
                $stats['succeeded']++;
                $this->line("  REFUNDED late payment {$row->provider_payment_id}");
            }
        });

        return $stats;
    }

    private function backoffElapsed($timestamp, int $attempts): bool
    {
        if ($timestamp === null) {
            return true;
        }

        return $timestamp->lte(now()->subMinutes(15 * max($attempts, 1)));
    }
}
