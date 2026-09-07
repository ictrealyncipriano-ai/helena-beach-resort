<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Focused policy coverage for the B1 AuthZ rollout.
 *
 * Policies are the primary gate; AdminMiddleware remains as defense-in-depth.
 * Export / ActivityLog / Availability / Dashboard intentionally have no
 * policies and are not covered here.
 */
class AdminAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_staff_is_denied_on_policy_protected_resources(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);

        $this->actingAs($staff)->get(route('admin.cottages.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('admin.posts.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('admin.site-settings.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('admin.guests.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('admin.promo-codes.index'))->assertForbidden();
        $this->actingAs($staff)->post(route('admin.faqs.activate-all'))->assertForbidden();
    }

    public function test_staff_keeps_inquiry_and_dashboard_access(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);

        $this->actingAs($staff)->get(route('admin.inquiries.index'))->assertOk();
        $this->actingAs($staff)->get(route('admin.dashboard'))->assertOk();
    }

    public function test_admin_can_read_but_not_write_site_settings(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $setting = SiteSetting::firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.site-settings.index'))
            ->assertOk();

        $this->actingAs($admin)
            ->put(route('admin.site-settings.update', $setting), [
                'key' => $setting->key,
                'value' => 'admin attempt',
                'type' => $setting->type,
            ])
            ->assertForbidden();
    }

    public function test_super_admin_can_write_site_settings(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $setting = SiteSetting::firstOrFail();

        $this->actingAs($superAdmin)
            ->put(route('admin.site-settings.update', $setting), [
                'key' => $setting->key,
                'value' => 'super admin value',
                'type' => $setting->type,
            ])
            ->assertRedirect(route('admin.site-settings.index'));

        $this->assertDatabaseHas('site_settings', [
            'id' => $setting->id,
            'value' => 'super admin value',
        ]);
    }

    public function test_admin_and_super_admin_keep_resource_access(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('admin.cottages.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.guests.index'))->assertOk();
        $this->actingAs($admin)->post(route('admin.faqs.activate-all'))->assertRedirect();
    }
}
