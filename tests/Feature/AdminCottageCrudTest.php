<?php

namespace Tests\Feature;

use App\Models\Cottage;
use App\Models\Inquiry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Phase 9.1 — closes the remaining direct-coverage gaps on the admin
 * CottageController (not covered by CottageUploadValidationTest or
 * CottageUpdatePreservesBlocksTest): index search/filter, create/edit
 * rendering and the destroy active-booking guard.
 */
class AdminCottageCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('cloudflare');
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'super_admin']);
    }

    private function cottage(array $overrides = []): Cottage
    {
        return Cottage::create(array_merge([
            'name' => 'Beach Villa',
            'slug' => 'beach-villa',
            'rate_daytour' => 1500,
            'rate_overnight' => 3000,
            'is_available' => true,
        ], $overrides));
    }

    public function test_index_renders_cottages_with_search(): void
    {
        $this->cottage(['name' => 'Seaside Villa', 'slug' => 'seaside-villa']);
        $this->cottage(['name' => 'Garden Hut', 'slug' => 'garden-hut']);

        $this->actingAs($this->admin())
            ->get(route('admin.cottages.index', ['search' => 'Seaside']))
            ->assertOk()
            ->assertSee('Seaside Villa', false)
            ->assertDontSee('Garden Hut', false);
    }

    public function test_index_availability_filter(): void
    {
        $this->cottage(['name' => 'Avail Villa', 'slug' => 'avail', 'is_available' => true]);
        $this->cottage(['name' => 'Closed Villa', 'slug' => 'closed', 'is_available' => false]);

        $this->actingAs($this->admin())
            ->get(route('admin.cottages.index', ['availability' => 'unavailable']))
            ->assertOk()
            ->assertSee('Closed Villa', false)
            ->assertDontSee('Avail Villa', false);
    }

    public function test_create_and_edit_render(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.cottages.create'))->assertOk();

        $cottage = $this->cottage(['name' => 'Edit Me', 'slug' => 'edit-me']);
        $this->actingAs($admin)->get(route('admin.cottages.edit', $cottage))->assertOk()
            ->assertSee('Edit Me', false);
    }

    public function test_destroy_blocks_when_cottage_has_active_inquiry(): void
    {
        $cottage = $this->cottage(['name' => 'Held', 'slug' => 'held']);

        $inquiry = Inquiry::create([
            'reference_code' => Inquiry::generateReferenceCode(),
            'name' => 'Guest',
            'email' => 'guest@example.com',
            'check_in' => '2026-09-01',
            'check_out' => '2026-09-03',
            'cottage_id' => $cottage->id,
            'status' => 'pending',
            'source' => 'website',
        ]);
        $inquiry->reserveBlocks();

        $this->actingAs($this->admin())
            ->delete(route('admin.cottages.destroy', $cottage))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('cottages', ['id' => $cottage->id]);
    }

    public function test_destroy_succeeds_when_no_active_inquiry(): void
    {
        $cottage = $this->cottage(['name' => 'Free', 'slug' => 'free']);
        // An admin-only (non-inquiry) block does not block deletion.
        $cottage->dateBlocks()->create(['date' => '2026-12-25', 'reason' => 'Maintenance']);

        $this->actingAs($this->admin())
            ->delete(route('admin.cottages.destroy', $cottage))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('cottages', ['id' => $cottage->id]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'cottage.deleted', 'subject_id' => $cottage->id]);
    }

    public function test_destroy_deletes_cottage_photos_from_cloudflare(): void
    {
        $cottage = $this->cottage(['name' => 'With Photos', 'slug' => 'with-photos']);
        $photo = $cottage->photos()->create(['photo_path' => 'cottages/p.jpg', 'is_primary' => true]);
        Storage::disk('cloudflare')->put('cottages/p.jpg', 'PIC');

        $this->actingAs($this->admin())
            ->delete(route('admin.cottages.destroy', $cottage))
            ->assertRedirect();

        $this->assertFalse(Storage::disk('cloudflare')->exists('cottages/p.jpg'));
        $this->assertDatabaseMissing('cottage_photos', ['id' => $photo->id]);
    }

    public function test_staff_cannot_access_cottages(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'staff']))
            ->get(route('admin.cottages.index'))
            ->assertForbidden();
    }
}
