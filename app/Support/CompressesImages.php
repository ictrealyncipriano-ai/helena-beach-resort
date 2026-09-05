<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Compress uploaded images in place (JPEG/PNG/WebP) when the compressed
 * version is smaller than the original. Downscales to 1600px max dimension,
 * converts a WebP copy alongside the original when smaller, and returns
 * intrinsic dimensions so callers can emit width/height (CLS).
 * Uploads live on the 'cloudflare' disk, so reads and writes go through
 * that disk to avoid silently missing.
 */
trait CompressesImages
{
    private const MAX_DIMENSION = 1600;

    /**
     * @return array{width:int,height:int}|null intrinsic size when readable.
     */
    protected static function compressImage(string $path): ?array
    {
        try {
            // Uploads are stored on the 'cloudflare' disk (see the admin
            // controllers), so compression must read/write the same disk
            // or it silently never runs.
            $disk = Storage::disk('cloudflare');
        } catch (\Throwable) {
            return null;
        }

        if (! $disk->exists($path)) {
            return null;
        }

        $tmpPath = tempnam(sys_get_temp_dir(), 'image_compress_');
        $raw = $disk->get($path);
        if ($raw === null || file_put_contents($tmpPath, $raw) === false) {
            @unlink($tmpPath);

            return null;
        }

        $info = @getimagesize($tmpPath);
        if (! $info) {
            @unlink($tmpPath);

            return null;
        }

        $dims = ['width' => $info[0], 'height' => $info[1]];
        $originalSize = $disk->size($path);
        $mime = $info['mime'];

        try {
            $resized = static::downscale($tmpPath, $mime);
            if ($resized) {
                $dims = $resized;
            }

            match ($mime) {
                'image/jpeg' => static::compressJpeg($tmpPath),
                'image/png' => static::compressPng($tmpPath),
                'image/webp' => static::compressWebp($tmpPath),
                default => null,
            };

            $compressedSize = filesize($tmpPath);
            if ($compressedSize !== false && $compressedSize < $originalSize) {
                $disk->put($path, file_get_contents($tmpPath) ?: '', ['visibility' => 'public']);
            }

            // WebP copy alongside the original (same basename, .webp) when
            // it is smaller — frontend serves it via <picture> fallback.
            if (in_array($mime, ['image/jpeg', 'image/png'], true) && function_exists('imagewebp')) {
                static::writeWebpCopy($disk, $path, $tmpPath);
            }

            return $dims;
        } finally {
            @unlink($tmpPath);
        }
    }

    /**
     * Downscale oversized images to MAX_DIMENSION, preserving aspect and
     * transparency. Returns new dims when resized, null otherwise.
     *
     * @return array{width:int,height:int}|null
     */
    protected static function downscale(string $path, string $mime): ?array
    {
        $src = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png' => @imagecreatefrompng($path),
            'image/webp' => @imagecreatefromwebp($path),
            default => null,
        };
        if (! $src) {
            return null;
        }

        $w = imagesx($src);
        $h = imagesy($src);
        $max = max($w, $h);
        if ($max <= self::MAX_DIMENSION) {
            imagedestroy($src);

            return null;
        }

        $scale = self::MAX_DIMENSION / $max;
        $nw = (int) round($w * $scale);
        $nh = (int) round($h * $scale);
        $dst = imagecreatetruecolor($nw, $nh);
        if (in_array($mime, ['image/png', 'image/webp'], true)) {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
        }
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
        imagedestroy($src);

        $ok = match ($mime) {
            'image/jpeg' => imagejpeg($dst, $path, 82),
            'image/png' => imagepng($dst, $path, 6),
            'image/webp' => imagewebp($dst, $path, 80),
            default => false,
        };
        imagedestroy($dst);

        return $ok ? ['width' => $nw, 'height' => $nh] : null;
    }

    protected static function writeWebpCopy($disk, string $path, string $tmpPath): void
    {
        $info = @getimagesize($tmpPath);
        if (! $info) {
            return;
        }
        $src = match ($info['mime']) {
            'image/jpeg' => @imagecreatefromjpeg($tmpPath),
            'image/png' => @imagecreatefrompng($tmpPath),
            default => null,
        };
        if (! $src) {
            return;
        }
        $webpTmp = tempnam(sys_get_temp_dir(), 'image_webp_');
        $ok = @imagewebp($src, $webpTmp, 78);
        imagedestroy($src);
        if (! $ok) {
            @unlink($webpTmp);

            return;
        }
        $webpSize = filesize($webpTmp);
        $webpPath = preg_replace('/\.(jpe?g|png)$/i', '.webp', $path);
        if ($webpPath && $webpSize !== false && $webpSize < $disk->size($path)) {
            $disk->put($webpPath, file_get_contents($webpTmp) ?: '', ['visibility' => 'public']);
        }
        @unlink($webpTmp);
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
