<?php

namespace App\Traits;

use Illuminate\Support\Facades\Storage;

trait ManagesCloudflareFiles
{
    /**
     * Delete a file from the Cloudflare R2 / S3-compatible disk.
     * Silently ignores null or empty paths so callers don't need a guard.
     */
    protected function deleteFromCloudflare(?string $path): void
    {
        if ($path) {
            Storage::disk('cloudflare')->delete($path);
        }
    }
}
