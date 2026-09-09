<?php

namespace App\Models;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
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
     * Retrieve a setting as a positive integer within bounds, for rule-type
     * settings (booking cutoff/hold hours). Anything missing, non-numeric,
     * or out of range falls back to the default so bad DB values fail
     * safely into today's behavior instead of surprising rules.
     */
    public static function intValue(string $key, int $default, int $min = 1, int $max = PHP_INT_MAX): int
    {
        $raw = trim((string) static::getValue($key, ''));

        if (! ctype_digit($raw)) {
            return $default;
        }

        $value = (int) $raw;

        if ($value < $min || $value > $max) {
            return $default;
        }

        return $value;
    }

    /**
     * Resolve an image-type setting (Storage path) to a public URL, falling
     * back to a shipped asset when unconfigured. Single source for brand
     * assets so web views, emails and PDFs never hardcode logo paths.
     */
    public static function assetUrl(string $key, string $fallbackAsset): string
    {
        $path = trim((string) static::getValue($key, ''));

        return $path !== '' ? Storage::url($path) : asset($fallbackAsset);
    }

    public static function logoUrl(): string
    {
        return static::assetUrl('site_logo', 'images/logo.jpg');
    }

    public static function faviconUrl(): string
    {
        return static::assetUrl('site_favicon', 'favicon.ico');
    }

    public static function appleTouchIconUrl(): string
    {
        return static::assetUrl('site_favicon', 'apple-touch-icon.png');
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
