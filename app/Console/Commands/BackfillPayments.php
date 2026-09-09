<?php

namespace App\Console\Commands;

use App\Models\Inquiry;
use App\Models\Payment;
use Illuminate\Console\Command;

/**
 * WP-1: idempotent backfill of the payments ledger from legacy
 * inquiries.* summary columns. Safe to re-run: inquiries that already
 * carry ledger rows are skipped, online rows dedupe on
 * provider_payment_id, manual rows dedupe on inquiry+amount.
 */
class BackfillPayments extends Command
{
    protected $signature = 'payments:backfill {--dry-run : Report what would be created without writing}';

    protected $description = 'Backfill the payments ledger from inquiries payment columns (idempotent)';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $created = 0;
        $skipped = 0;

        Inquiry::withTrashed()->chunkById(100, function ($inquiries) use ($dryRun, &$created, &$skipped) {
            foreach ($inquiries as $inquiry) {
                $collected = (float) ($inquiry->amount_paid ?? 0);
                $hasOnline = $inquiry->paymongo_payment_id !== null;
                $hasMoney = $collected > 0 || $hasOnline;

                if (! $hasMoney && $inquiry->refunded_at === null) {
                    continue;
                }

                // Already backfilled / dual-written: never duplicate.
                if (Payment::where('inquiry_id', $inquiry->id)->exists()) {
                    $skipped++;
                    continue;
                }

                $type = $inquiry->hasDeposit() && $collected < (float) $inquiry->total_amount
                    ? Payment::TYPE_DEPOSIT
                    : Payment::TYPE_FULL;

                if ($dryRun) {
                    $created++;
                    continue;
                }

                if ($hasOnline) {
                    Payment::firstOrCreate(
                        ['provider_payment_id' => $inquiry->paymongo_payment_id],
                        [
                            'inquiry_id' => $inquiry->id,
                            'provider' => Payment::PROVIDER_PAYMONGO,
                            'provider_checkout_id' => $inquiry->paymongo_session_id,
                            'method' => $inquiry->payment_method,
                            'type' => $type,
                            'amount' => $inquiry->collectedAmount(),
                            'currency' => 'PHP',
                            'status' => $inquiry->refunded_at
                                ? Payment::STATUS_REFUNDED
                                : Payment::STATUS_PAID,
                            'paid_at' => $inquiry->fully_paid_at ?? $inquiry->deposit_paid_at ?? $inquiry->updated_at,
                            'refunded_at' => $inquiry->refunded_at,
                            'metadata' => ['backfilled' => true],
                        ]
                    );
                } elseif ($collected > 0) {
                    Payment::create([
                        'inquiry_id' => $inquiry->id,
                        'provider' => Payment::PROVIDER_MANUAL,
                        'method' => $inquiry->payment_method ?? Inquiry::METHOD_MANUAL,
                        'type' => $type,
                        'amount' => $inquiry->collectedAmount(),
                        'currency' => 'PHP',
                        'status' => $inquiry->refunded_at
                            ? Payment::STATUS_REFUNDED
                            : Payment::STATUS_PAID,
                        'paid_at' => $inquiry->fully_paid_at ?? $inquiry->deposit_paid_at ?? $inquiry->updated_at,
                        'refunded_at' => $inquiry->refunded_at,
                        'metadata' => ['backfilled' => true],
                    ]);
                }

                if ($inquiry->refunded_at !== null) {
                    Payment::create([
                        'inquiry_id' => $inquiry->id,
                        'provider' => $hasOnline ? Payment::PROVIDER_PAYMONGO : Payment::PROVIDER_MANUAL,
                        'method' => $inquiry->payment_method,
                        'type' => Payment::TYPE_REFUND,
                        'amount' => (string) ($inquiry->refund_amount ?? $inquiry->collectedAmount()),
                        'currency' => 'PHP',
                        'status' => Payment::STATUS_REFUNDED,
                        'refunded_at' => $inquiry->refunded_at,
                        'metadata' => ['backfilled' => true],
                    ]);
                }

                $created++;
            }
        });

        $this->info("Done: {$created} inquiry(ies) backfilled, {$skipped} skipped (already have ledger rows).");

        return self::SUCCESS;
    }
}
