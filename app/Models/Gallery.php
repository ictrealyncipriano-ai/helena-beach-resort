<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Gallery extends Model
{
    protected $fillable = ['title', 'photo_path', 'category', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saving(function (Gallery $gallery) {
            if ($gallery->isDirty('photo_path') && $gallery->photo_path) {
                static::compressImage($gallery->photo_path);
            }
        });
    }

    protected static function compressImage(string $path): void
    {
        $disk = Storage::disk('r2');

        if (!$disk->exists($path)) {
            return;
        }

        $tmpPath = tempnam(sys_get_temp_dir(), 'gallery_compress_');
        file_put_contents($tmpPath, $disk->get($path));

        $info = @getimagesize($tmpPath);
        if (!$info) {
            @unlink($tmpPath);
            return;
        }

        $mime = $info['mime'];
        $src = null;

        try {
            match ($mime) {
                'image/jpeg' => static::compressJpeg($tmpPath),
                'image/png' => static::compressPng($tmpPath),
                'image/webp' => static::compressWebp($tmpPath),
                default => null,
            };

            $disk->put($path, file_get_contents($tmpPath), 'public');
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
