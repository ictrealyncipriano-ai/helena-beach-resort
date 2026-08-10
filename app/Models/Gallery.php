<?php

namespace App\Models;

use App\Support\CompressesImages;
use App\Support\PublicCache;
use Illuminate\Database\Eloquent\Model;

class Gallery extends Model
{
    use CompressesImages;

    protected $fillable = ['title', 'photo_path', 'category', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /**
     * Boot events: automatically compress uploaded images on save.
     */
    protected static function booted(): void
    {
        static::saving(function (Gallery $gallery) {
            if ($gallery->isDirty('photo_path') && $gallery->photo_path) {
                static::compressImage($gallery->photo_path);
            }
        });

        static::saved(fn () => PublicCache::flush());
        static::deleted(fn () => PublicCache::flush());
    }
}
