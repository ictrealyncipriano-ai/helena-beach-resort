<?php

namespace App\Models;

use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $fillable = ['key', 'value', 'type'];

    /**
     * Boot events: clear cached settings whenever a setting is saved or deleted.
     */
    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('settings.all'));
        static::deleted(fn () => Cache::forget('settings.all'));
    }

    /**
     * Retrieve a setting value by key, with optional default fallback.
     * Results are cached indefinitely for performance.
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        return Cache::rememberForever('settings.all', fn () =>
            static::pluck('value', 'key')->all()
        )[$key] ?? $default;
    }
}
