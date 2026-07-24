<?php

namespace App\Console\Commands;

use App\Models\Gallery;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class SyncGalleries extends Command
{
    protected $signature = 'galleries:sync';
    protected $description = 'Ensure all gallery images exist on the configured storage disk';

    public function handle(): int
    {
        $disk = Storage::disk(env('FILESYSTEM_DISK', 'public'));
        $fixed = 0;
        $skipped = 0;

        foreach (Gallery::cursor() as $gallery) {
            $path = $gallery->photo_path;

            if ($disk->exists($path)) {
                $this->line("  OK   {$gallery->title}");
                $skipped++;
                continue;
            }

            $source = Storage::disk('public');
            if ($source->exists($path)) {
                $contents = $source->get($path);
                $disk->put($path, $contents, 'public');
                $this->line("  SYNC {$gallery->title}");
                $fixed++;
                continue;
            }

            $label = $gallery->title ?: 'Gallery';
            $svg = $this->makeSvg($label);
            $disk->put($path, $svg, 'public');
            $this->line("  GEN  {$gallery->title}");
            $fixed++;
        }

        $this->newLine();
        $this->info("Done: $fixed generated/synced, $skipped skipped.");

        return self::SUCCESS;
    }

    private function makeSvg(string $label): string
    {
        $w = 800;
        $h = 600;
        $color1 = '#0d9488';
        $color2 = '#0f766e';
        $escaped = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="{$w}" height="{$h}" viewBox="0 0 {$w} {$h}">
  <defs>
    <linearGradient id="bg" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" style="stop-color:{$color1}"/>
      <stop offset="100%" style="stop-color:{$color2}"/>
    </linearGradient>
  </defs>
  <rect width="{$w}" height="{$h}" fill="url(#bg)"/>
  <text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle"
        font-family="Arial,sans-serif" font-size="48" font-weight="bold" fill="white">{$escaped}</text>
</svg>
SVG;
    }
}
