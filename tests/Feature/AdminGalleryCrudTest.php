<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Gallery;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Phase 9.1 — the admin GalleryController previously had no direct coverage.
 * These tests lock in the full CRUD lifecycle including the Cloudflare
 * upload/delete behaviour.
 */
class AdminGalleryCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('cloudflare');
    }

    private function gallery(array $overrides = []): Gallery
    {
        return Gallery::create(array_merge([
            'title' => 'Sunset',
            'photo_path' => 'gallery/fixture.jpg',
            'sort_order' => 1,
            'is_active' => true,
        ], $overrides));
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'super_admin']);
    }

    public function test_index_renders_galleries(): void
    {
        $this->gallery(['title' => 'Sunset', 'category' => 'Beach']);

        $this->actingAs($this->admin())
            ->get(route('admin.gallery.index'))
            ->assertOk()
            ->assertSee('Sunset', false);
    }

    public function test_index_search_filters_gallery(): void
    {
        $this->gallery(['title' => 'Sunset']);
        $this->gallery(['title' => 'Surfing']);

        $this->actingAs($this->admin())
            ->get(route('admin.gallery.index', ['search' => 'Surf']))
            ->assertOk()
            ->assertSee('Surfing', false)
            ->assertDontSee('Sunset', false);
    }

    public function test_create_and_edit_views_render(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.gallery.create'))->assertOk();

        $gallery = $this->gallery(['title' => 'A']);
        $this->actingAs($admin)->get(route('admin.gallery.edit', $gallery))->assertOk();
    }

    public function test_store_creates_gallery_and_uploads_to_cloudflare(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.gallery.store'), [
                'title' => 'Beach Day',
                'category' => 'Beach',
                'sort_order' => 1,
                'is_active' => '1',
                'photo_path' => UploadedFile::fake()->image('beach.jpg', 20, 20),
            ])
            ->assertRedirect(route('admin.gallery.index'));

        $gallery = Gallery::where('title', 'Beach Day')->first();
        $this->assertNotNull($gallery);
        $this->assertStringStartsWith('gallery/', $gallery->photo_path);
        $this->assertTrue(Storage::disk('cloudflare')->exists($gallery->photo_path));

        $this->assertDatabaseHas('activity_logs', ['action' => 'gallery.created', 'subject_id' => $gallery->id]);
    }

    public function test_store_requires_photo(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.gallery.store'), ['title' => 'No Photo'])
            ->assertSessionHasErrors('photo_path');

        $this->assertDatabaseCount('galleries', 0);
    }

    public function test_update_changes_fields_and_replaces_photo(): void
    {
        $gallery = $this->gallery(['title' => 'Old', 'photo_path' => 'gallery/old.jpg']);
        Storage::disk('cloudflare')->put('gallery/old.jpg', 'OLD');

        $this->actingAs($this->admin())
            ->put(route('admin.gallery.update', $gallery), [
                'title' => 'New',
                'category' => 'Events',
                'is_active' => '1',
                'photo_path' => UploadedFile::fake()->image('new.jpg', 20, 20),
            ])
            ->assertRedirect(route('admin.gallery.index'));

        $gallery->refresh();
        $this->assertSame('New', $gallery->title);
        $this->assertSame('Events', $gallery->category);
        $this->assertStringStartsWith('gallery/', $gallery->photo_path);
        // Old file deleted from the cloudflare disk.
        $this->assertFalse(Storage::disk('cloudflare')->exists('gallery/old.jpg'));

        $this->assertDatabaseHas('activity_logs', ['action' => 'gallery.updated', 'subject_id' => $gallery->id]);
    }

    public function test_destroy_deletes_gallery_and_cloudflare_file(): void
    {
        $gallery = $this->gallery(['title' => 'Gone', 'photo_path' => 'gallery/gone.jpg']);
        Storage::disk('cloudflare')->put('gallery/gone.jpg', 'BYE');

        $this->actingAs($this->admin())
            ->delete(route('admin.gallery.destroy', $gallery))
            ->assertRedirect(route('admin.gallery.index'));

        $this->assertDatabaseMissing('galleries', ['id' => $gallery->id]);
        $this->assertFalse(Storage::disk('cloudflare')->exists('gallery/gone.jpg'));
        $this->assertDatabaseHas('activity_logs', ['action' => 'gallery.deleted', 'subject_id' => $gallery->id]);
    }

    public function test_staff_cannot_access_gallery(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'staff']))
            ->get(route('admin.gallery.index'))
            ->assertForbidden();
    }
}
