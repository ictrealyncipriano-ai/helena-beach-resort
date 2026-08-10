<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSiteSettingPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_admin_can_view_site_settings(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.site-settings.index'))
            ->assertStatus(200)
            ->assertSee('analytics_ga4_id')
            ->assertSee('Add Setting');
    }

    public function test_staff_is_forbidden_from_site_settings(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);

        $this->actingAs($staff)
            ->get(route('admin.site-settings.index'))
            ->assertStatus(403);
    }

    public function test_search_filters_settings_by_key(): void
    {
        SiteSetting::updateOrCreate(['key' => 'hero_heading'], ['value' => 'Helena Beach Resort']);
        SiteSetting::updateOrCreate(['key' => 'analytics_ga4_id'], ['value' => 'G-XXXX']);
        SiteSetting::updateOrCreate(['key' => 'section_cta_heading'], ['value' => 'Ready for a Getaway?']);

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.site-settings.index', ['search' => 'analytics']))
            ->assertStatus(200)
            ->assertSee('analytics_ga4_id')
            ->assertDontSee('hero_heading')
            ->assertDontSee('section_cta_heading');
    }

    public function test_search_filters_settings_by_value(): void
    {
        SiteSetting::updateOrCreate(['key' => 'analytics_ga4_id'], ['value' => 'G-XXXX']);

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.site-settings.index', ['search' => 'G-XXXX']))
            ->assertStatus(200)
            ->assertSee('analytics_ga4_id');
    }

    public function test_search_with_no_matches_shows_empty_state(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.site-settings.index', ['search' => 'no-such-setting-xyz']))
            ->assertStatus(200)
            ->assertDontSee('site_name')
            ->assertSee('No settings');
    }
}
