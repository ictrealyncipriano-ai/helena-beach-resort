<?php

namespace App\Console\Commands;

use App\Models\Inquiry;
use Illuminate\Console\Command;

class ReleaseExpiredReservations extends Command
{
    protected $signature = 'reservations:release-expired {--hours=48 : Minimum age in hours before a pending reservation expires}';

    protected $description = 'Expire pending inquiries past the hold window and release their cottage date blocks';

    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        $cutoff = now()->subHours($hours);

        $expired = Inquiry::pending()
            ->where('created_at', '<=', $cutoff)
            ->get();

        foreach ($expired as $inquiry) {
            $inquiry->update(['status' => 'expired']);
            $inquiry->releaseBlocks();
            $this->line("  EXPIRED {$inquiry->reference_code} ({$inquiry->name})");
        }

        $this->newLine();
        $this->info("Done: {$expired->count()} expired reservation(s) released.");

        return self::SUCCESS;
    }
}
