<?php

namespace App\Models;

use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $fillable = ['key', 'value', 'type'];

    /**
     * Request-scoped memo of all settings (key => value).
     *
     * With CACHE_STORE=database every Cache::remember() call is a cache-table
     * SELECT, and the homepage alone reads settings ~21x per request. Loading
     * the full map once per request (backed by the shared 'settings.all'
     * cache) cuts that down to a single cache lookup per request.
     */
    private static ?array $memo = null;

    /**
     * Boot events: clear the memoized settings whenever a setting is saved
     * or deleted so admin edits reflect immediately.
     */
    protected static function booted(): void
    {
        static::saved(fn () => static::forgetCache());
        static::deleted(fn () => static::forgetCache());
    }

    /**
     * Retrieve a setting value by key, with optional default fallback.
     *
     * All settings are loaded once per request into a static memo, which is
     * itself populated from the shared 'settings.all' cache across requests.
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        if (static::$memo === null) {
            static::$memo = Cache::rememberForever('settings.all', fn () =>
                static::pluck('value', 'key')->all()
            );
        }

        return static::$memo[$key] ?? $default;
    }

    /**
     * Drop the request memo and the shared cache entry so the next getValue()
     * re-reads the settings from the database.
     */
    public static function forgetCache(): void
    {
        static::$memo = null;
        Cache::forget('settings.all');
    }
}
