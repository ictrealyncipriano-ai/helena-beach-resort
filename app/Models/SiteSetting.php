<?php

namespace App\Models;

use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $fillable = ['key', 'value', 'type'];

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('settings.all'));
        static::deleted(fn () => Cache::forget('settings.all'));
    }

    public static function getValue(string $key, mixed $default = null): mixed
    {
        return Cache::rememberForever('settings.all', fn () =>
            static::pluck('value', 'key')->all()
        )[$key] ?? $default;
    }
}
