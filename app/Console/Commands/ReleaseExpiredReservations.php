<?php

namespace App\Console\Commands;

use App\Mail\BookingExpired;
use App\Mail\BookingExpiringSoon;
use App\Http\Controllers\Admin\DashboardController;
use App\Models\CottageDateBlock;
use App\Models\Inquiry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ReleaseExpiredReservations extends Command
{
    // Default hold window in hours. Artisan option defaults must be string
    // literals, so the signature keeps 48 below and CronController references
    // this constant when invoking the command.
    public const DEFAULT_HOLD_HOURS = 48;

    protected $signature = 'reservations:release-expired {--hours=48 : Minimum age in hours before a pending reservation expires}';

    protected $description = 'Expire pending inquiries past the hold window, warn those expiring soon, and release their cottage date blocks';

    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        $cutoff = now()->subHours($hours);

        $count = 0;

        // First, warn guests whose pending requests are within ~12h of the
        // hold window ending (created $hours-24 to $hours-12 hours ago).
        // Idempotent: only inquiries with a null expiry_warned_at are emailed.
        $warned = $this->warnExpiringSoon($hours);

        // Process in chunks instead of hydrating every expired row at once.
        // Within each chunk the date blocks are collected with a single
        // batched SELECT, deleted with a single batched DELETE, and the
        // inquiries are marked expired with a single UPDATE.
        Inquiry::pending()
            ->where('created_at', '<=', $cutoff)
            ->chunkById(100, function ($inquiries) use (&$count) {
                // A block's reason encodes the owning inquiry's reference code
                // (e.g. "Pending: HB-ABC123"), which is unique per inquiry, so
                // matching cottage + date range + reason here preserves the
                // per-inquiry releaseBlocks() semantics. One grouped OR query
                // covers the whole chunk instead of one SELECT per inquiry.
                $blockIds = CottageDateBlock::query()
                    ->where(function ($q) use ($inquiries) {
                        foreach ($inquiries as $inquiry) {
                            if (! $inquiry->cottage_id || ! $inquiry->check_in || ! $inquiry->reference_code) {
                                continue;
                            }

                            $checkIn = $inquiry->check_in->format('Y-m-d');
                            $checkOut = ($inquiry->check_out ?? $inquiry->check_in)->format('Y-m-d');

                            $q->orWhere(function ($sub) use ($inquiry, $checkIn, $checkOut) {
                                $sub->where('cottage_id', $inquiry->cottage_id)
                                    ->whereBetween('date', [$checkIn, $checkOut])
                                    ->whereIn('reason', [
                                        "Pending: {$inquiry->reference_code}",
                                        "Booked: {$inquiry->reference_code}",
                                    ]);
                            });
                        }
                    })
                    ->pluck('id');

                CottageDateBlock::whereIn('id', $blockIds)->delete();

                // Bulk-mark every inquiry in this chunk as expired in one
                // UPDATE instead of one per row.
                Inquiry::whereIn('id', $inquiries->pluck('id'))
                    ->update(['status' => Inquiry::STATUS_EXPIRED]);

                foreach ($inquiries as $inquiry) {
                    $this->line("  EXPIRED {$inquiry->reference_code} ({$inquiry->name})");
                    $this->notifyExpired($inquiry);
                }

                $count += $inquiries->count();
            });

        $this->newLine();
        $this->info("Done: {$count} expired reservation(s) released.");
        if ($warned > 0) {
            $this->info("Warned {$warned} reservation(s) expiring soon.");
        }

        // Expired statuses change the dashboard's pending counts, so drop the
        // cached stats block after any expiries were processed.
        if ($count > 0) {
            DashboardController::forgetCache();
        }

        return self::SUCCESS;
    }

    /**
     * Email guests whose pending requests are within ~24h of their hold window
     * ending (i.e. created between $hours and $hours-24 hours ago). Only
     * inquiries that have never been warned are picked up, and each is marked
     * via expiry_warned_at so repeated cron runs never spam the same guest.
     *
     * The window is anchored on time-remaining-to-expiry rather than a fixed
     * age band: any pending inquiry with a null expiry_warned_at whose hold
     * expires inside the next 24h is warned. Vercel Hobby cron only fires once
     * per day, so a 24h-wide window guarantees every booking is caught at least
     * one run before it expires regardless of when it was created.
     */
    private function warnExpiringSoon(int $hours): int
    {
        $olderBound = now()->subHours($hours); // expires right now
        $newerBound = now()->subHours(max($hours - 24, 1)); // expires in ~24h

        $count = 0;

        Inquiry::pending()
            ->whereNull('expiry_warned_at')
            ->whereBetween('created_at', [$olderBound, $newerBound])
            ->chunkById(100, function ($inquiries) use (&$count) {
                foreach ($inquiries as $inquiry) {
                    try {
                        Mail::to($inquiry->email)->send(new BookingExpiringSoon($inquiry));
                        $inquiry->update(['expiry_warned_at' => now()]);
                        $this->line("  WARNED {$inquiry->reference_code} ({$inquiry->name})");
                        $count++;
                    } catch (\Exception $e) {
                        Log::warning('Failed to send expiry warning', [
                            'inquiry_id' => $inquiry->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });

        return $count;
    }

    /**
     * Notify the guest that their request has been expired and the dates
     * released, inviting them to book again. Emails are queued and failures
     * are logged rather than failing the whole run.
     */
    private function notifyExpired(Inquiry $inquiry): void
    {
        try {
            Mail::to($inquiry->email)->send(new BookingExpired($inquiry));
        } catch (\Exception $e) {
            Log::warning('Failed to send expiry notification', [
                'inquiry_id' => $inquiry->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
