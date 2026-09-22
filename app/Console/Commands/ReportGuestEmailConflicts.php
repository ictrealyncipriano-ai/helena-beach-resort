<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Slice 10 — read-only report of guest rows sharing a normalized email
 * (TRIM + lowercase, the findByEmailOrCreate() rule). Exits non-zero when
 * conflicts exist so the email_normalized migration guard (and CI) can
 * fail closed. Never modifies guest records: resolution is manual.
 */
class ReportGuestEmailConflicts extends Command
{
    protected $signature = 'guests:email-conflicts';

    protected $description = 'List guest rows sharing a normalized email without modifying anything';

    public function handle(): int
    {
        $groups = DB::table('guests')
            ->selectRaw('LOWER(TRIM(email)) as normalized, COUNT(*) as total')
            ->whereNotNull('email')
            ->groupBy('normalized')
            ->having('total', '>', 1)
            ->orderBy('normalized')
            ->get();

        if ($groups->isEmpty()) {
            $this->info('No normalized guest-email conflicts.');

            return self::SUCCESS;
        }

        foreach ($groups as $group) {
            $this->warn("{$group->normalized} ({$group->total} rows):");

            DB::table('guests')
                ->select('id', 'name', 'email', 'deleted_at')
                ->whereRaw('LOWER(TRIM(email)) = ?', [$group->normalized])
                ->orderBy('id')
                ->each(function ($row) {
                    $trashed = $row->deleted_at !== null ? ' [trashed]' : '';
                    $this->line("  #{$row->id} {$row->name} <{$row->email}>{$trashed}");
                });
        }

        return self::FAILURE;
    }
}
