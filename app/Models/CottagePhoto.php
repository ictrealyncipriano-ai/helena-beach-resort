<?php

namespace App\Models;

use App\Support\CompressesImages;
use App\Support\PublicCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CottagePhoto extends Model
{
    use CompressesImages;

    protected $fillable = ['cottage_id', 'photo_path', 'is_primary', 'sort_order', 'width', 'height'];

    protected function casts(): array
    {
        return ['is_primary' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saving(function (CottagePhoto $photo) {
            if ($photo->isDirty('photo_path') && $photo->photo_path) {
                $dims = static::compressImage($photo->photo_path);
                if ($dims) {
                    $photo->width = $dims['width'];
                    $photo->height = $dims['height'];
                }
            }
        });

        static::saved(fn () => PublicCache::flush());
        static::deleted(fn () => PublicCache::flush());
    }

    public function cottage(): BelongsTo
    {
        return $this->belongsTo(Cottage::class);
    }
}
