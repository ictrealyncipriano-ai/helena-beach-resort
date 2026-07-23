<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MigrateToR2 extends Command
{
    protected $signature = 'r2:migrate {disk=public : Source disk to migrate from}';
    protected $description = 'Migrate existing uploaded files from local storage to Cloudflare R2';

    public function handle(): int
    {
        $sourceDisk = $this->argument('disk');
        $source = Storage::disk($sourceDisk);
        $r2 = Storage::disk('r2');

        $directories = ['gallery', 'cottages'];
        $migrated = 0;
        $skipped = 0;

        foreach ($directories as $dir) {
            if (!$source->exists($dir)) {
                $this->warn("Directory '$dir' not found on source disk.");
                continue;
            }

            $files = $source->files($dir);
            $this->info("Found " . count($files) . " files in '$dir'.");

            foreach ($files as $file) {
                if ($r2->exists($file)) {
                    $this->line("  SKIP $file (already exists on R2)");
                    $skipped++;
                    continue;
                }

                $contents = $source->get($file);
                $r2->put($file, $contents, 'public');
                $this->line("  OK   $file");
                $migrated++;
            }
        }

        $this->newLine();
        $this->info("Migration complete: $migrated migrated, $skipped skipped.");

        return self::SUCCESS;
    }
}