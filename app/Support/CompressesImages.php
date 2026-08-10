<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Compress uploaded images in place (JPEG/PNG/WebP) when the compressed
 * version is smaller than the original. Uploads live on the 'cloudflare'
 * disk, so reads and writes go through that disk to avoid silently missing.
 */
trait CompressesImages
{
    protected static function compressImage(string $path): void
    {
        try {
            // Uploads are stored on the 'cloudflare' disk (see the admin
            // controllers), so compression must read/write the same disk
            // or it silently never runs.
            $disk = Storage::disk('cloudflare');
        } catch (\Throwable) {
            return;
        }

        if (! $disk->exists($path)) {
            return;
        }

        $tmpPath = tempnam(sys_get_temp_dir(), 'image_compress_');
        file_put_contents($tmpPath, $disk->get($path));

        $info = @getimagesize($tmpPath);
        if (! $info) {
            @unlink($tmpPath);

            return;
        }

        $originalSize = $disk->size($path);
        $mime = $info['mime'];

        try {
            match ($mime) {
                'image/jpeg' => static::compressJpeg($tmpPath),
                'image/png' => static::compressPng($tmpPath),
                'image/webp' => static::compressWebp($tmpPath),
                default => null,
            };

            $compressedSize = filesize($tmpPath);
            if ($compressedSize < $originalSize) {
                $disk->put($path, file_get_contents($tmpPath), 'public');
            }
        } finally {
            @unlink($tmpPath);
        }
    }

    protected static function compressJpeg(string $path): void
    {
        $src = @imagecreatefromjpeg($path);
        if ($src) {
            imagejpeg($src, $path, 75);
            imagedestroy($src);
        }
    }

    protected static function compressPng(string $path): void
    {
        $src = @imagecreatefrompng($path);
        if ($src) {
            imagealphablending($src, false);
            imagesavealpha($src, true);
            imagepng($src, $path, 6);
            imagedestroy($src);
        }
    }

    protected static function compressWebp(string $path): void
    {
        $src = @imagecreatefromwebp($path);
        if ($src) {
            imagewebp($src, $path, 75);
            imagedestroy($src);
        }
    }
}
