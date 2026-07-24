<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MigrateToCloudflare extends Command
{
    protected $signature = 'cloudflare:migrate {from=r2 : Source disk to migrate from}';
    protected $description = 'Migrate existing uploaded files to Cloudflare R2';

    public function handle(): int
    {
        $sourceDisk = $this->argument('from');
        $source = Storage::disk($sourceDisk);
        $dest = Storage::disk('cloudflare');

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
                if ($dest->exists($file)) {
                    $this->line("  SKIP $file (already exists on Cloudflare)");
                    $skipped++;
                    continue;
                }

                $contents = $source->get($file);
                $dest->put($file, $contents, 'public');
                $this->line("  OK   $file");
                $migrated++;
            }
        }

        $this->newLine();
        $this->info("Migration complete: $migrated migrated, $skipped skipped.");

        return self::SUCCESS;
    }
}
