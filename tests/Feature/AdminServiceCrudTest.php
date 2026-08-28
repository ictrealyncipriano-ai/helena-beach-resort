<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use App\Support\PublicCache;
use Tests\TestCase;

/**
 * Phase 9.1 — the admin ServiceController previously had no direct coverage.
 * Locks in CRUD + the live-search fragment + cache flushing.
 */
class AdminServiceCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'super_admin']);
    }

    public function test_index_renders_services(): void
    {
        Service::create(['name' => 'Massage', 'category' => 'Amenities', 'sort_order' => 1, 'is_active' => true]);

        $this->actingAs($this->admin())
            ->get(route('admin.services.index'))
            ->assertOk()
            ->assertSee('Massage', false);
    }

    public function test_index_category_filter(): void
    {
        Service::create(['name' => 'Massage', 'category' => 'Amenities', 'sort_order' => 1]);
        Service::create(['name' => 'Dinner', 'category' => 'Dining', 'sort_order' => 2]);

        $this->actingAs($this->admin())
            ->get(route('admin.services.index', ['category' => 'Dining']))
            ->assertOk()
            ->assertSee('Dinner', false)
            ->assertDontSee('Massage', false);
    }

    public function test_live_search_returns_table_fragment(): void
    {
        Service::create(['name' => 'Kayak', 'sort_order' => 1]);

        $this->actingAs($this->admin())
            ->get(route('admin.services.index', ['search' => 'Kay']), ['X-LiveSearch' => '1'])
            ->assertOk()
            ->assertSee('Kayak', false);
    }

    public function test_store_creates_service_and_records_activity(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.services.store'), [
                'name' => 'Guided Hike',
                'description' => 'A nature walk.',
                'icon' => 'tree',
                'category' => 'Activities',
                'sort_order' => 1,
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.services.index'));

        $service = Service::where('name', 'Guided Hike')->first();
        $this->assertNotNull($service);
        $this->assertTrue($service->is_active);
        $this->assertDatabaseHas('activity_logs', ['action' => 'service.created', 'subject_id' => $service->id]);
    }

    public function test_store_requires_name(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.services.store'), ['name' => ''])
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('services', 0);
    }

    public function test_update_changes_service(): void
    {
        $service = Service::create(['name' => 'Old', 'is_active' => true]);

        $this->actingAs($this->admin())
            ->put(route('admin.services.update', $service), [
                'name' => 'New Name',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.services.index'));

        $this->assertSame('New Name', $service->fresh()->name);
        $this->assertDatabaseHas('activity_logs', ['action' => 'service.updated', 'subject_id' => $service->id]);
    }

    public function test_destroy_deletes_service(): void
    {
        $service = Service::create(['name' => 'To Delete', 'is_active' => true]);

        $this->actingAs($this->admin())
            ->delete(route('admin.services.destroy', $service))
            ->assertRedirect(route('admin.services.index'));

        $this->assertDatabaseMissing('services', ['id' => $service->id]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'service.deleted', 'subject_id' => $service->id]);
    }

    public function test_staff_cannot_access_services(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'staff']))
            ->get(route('admin.services.index'))
            ->assertForbidden();
    }
}
