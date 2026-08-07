<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Phase 3.1 — SiteSettings are memoized per request (backed by the shared
 * 'settings.all' cache) so the homepage no longer issues ~21 cache-table
 * SELECTs. The memo must be invalidated on save/update/delete so admin
 * edits reflect immediately.
 */
class SiteSettingMemoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // The static memo lives for the whole PHPUnit process; reset it
        // between tests so one test can never leak into another.
        SiteSetting::forgetCache();
        Cache::flush();
    }

    public function test_get_value_returns_default_when_setting_missing(): void
    {
        $this->assertSame('fallback', SiteSetting::getValue('does_not_exist', 'fallback'));
        $this->assertNull(SiteSetting::getValue('does_not_exist'));
    }

    public function test_get_value_serves_from_memo_after_first_access(): void
    {
        SiteSetting::create(['key' => 'site_name', 'value' => 'Helena', 'type' => 'text']);

        // First access populates the request-scoped memo.
        SiteSetting::getValue('site_name');

        DB::flushQueryLog();
        DB::enableQueryLog();

        // Subsequent reads must be served from the memo, not the database.
        SiteSetting::getValue('site_name');
        SiteSetting::getValue('site_name');

        $settingQueries = collect(DB::getQueryLog())
            ->filter(fn ($q) => str_contains($q['query'], 'site_settings'))
            ->count();

        $this->assertSame(0, $settingQueries);
    }

    public function test_updated_setting_value_is_read_after_memo_populated(): void
    {
        $setting = SiteSetting::create(['key' => 'hero_heading', 'value' => 'Before', 'type' => 'text']);

        // Populate the memo.
        $this->assertSame('Before', SiteSetting::getValue('hero_heading'));

        // Model-level update (what the admin controller does) fires the
        // saved event, which must clear both the memo and the cache.
        $setting->update(['value' => 'After']);

        $this->assertSame('After', SiteSetting::getValue('hero_heading'));
    }

    public function test_deleted_setting_falls_back_to_default(): void
    {
        $setting = SiteSetting::create(['key' => 'hero_subtitle', 'value' => 'Some text', 'type' => 'textarea']);

        $this->assertSame('Some text', SiteSetting::getValue('hero_subtitle'));

        $setting->delete();

        $this->assertSame('default', SiteSetting::getValue('hero_subtitle', 'default'));
    }

    public function test_saving_clears_shared_cache_entry(): void
    {
        $setting = SiteSetting::create(['key' => 'contact_email', 'value' => 'a@example.com', 'type' => 'text']);
        SiteSetting::getValue('contact_email');

        // The shared cache entry exists once the memo has been populated.
        $this->assertTrue(Cache::has('settings.all'));

        $setting->update(['value' => 'b@example.com']);

        $this->assertFalse(Cache::has('settings.all'));
    }
}
