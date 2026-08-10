<?php

namespace App\Models;

use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $fillable = ['key', 'value', 'type'];

    /**
     * Cache key for the full ordered settings list used by the admin index
     * (id/key/value/type). Invalidated together with 'settings.all' whenever a
     * setting is saved or deleted.
     */
    public const ADMIN_CACHE_KEY = 'settings.admin.all';

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
     * Drop the request memo and the shared cache entries so the next getValue()
     * / cachedAll() re-reads the settings from the database.
     */
    public static function forgetCache(): void
    {
        static::$memo = null;
        Cache::forget('settings.all');
        Cache::forget(self::ADMIN_CACHE_KEY);
    }

    /**
     * The full settings list ordered by key, cached across requests. Used by
     * the admin index (which filters and paginates in memory), so searching
     * and paging over a small settings table stays off the database.
     */
    public static function cachedAll(): \Illuminate\Support\Collection
    {
        return Cache::rememberForever(self::ADMIN_CACHE_KEY, fn () =>
            static::query()->orderBy('key')->get(['id', 'key', 'value', 'type'])
        );
    }
}
