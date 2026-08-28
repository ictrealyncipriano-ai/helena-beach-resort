<?php

namespace Tests\Feature;

use App\Models\Cottage;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Phase 9.1 — the admin TestimonialController previously had no direct coverage.
 * Covers CRUD, avatar upload to Cloudflare, validation and role gating.
 */
class AdminTestimonialCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('cloudflare');
        $this->cottage = Cottage::create([
            'name' => 'Beach Hut',
            'slug' => 'beach-hut',
        ]);
    }

    private Cottage $cottage;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'super_admin']);
    }

    public function test_index_renders_testimonials(): void
    {
        Testimonial::create(['guest_name' => 'Maria', 'content' => 'Lovely stay', 'rating' => 5, 'is_active' => true]);

        $this->actingAs($this->admin())
            ->get(route('admin.testimonials.index'))
            ->assertOk()
            ->assertSee('Maria', false);
    }

    public function test_index_rating_filter(): void
    {
        Testimonial::create(['guest_name' => 'Five', 'content' => 'A', 'rating' => 5, 'is_active' => true]);
        Testimonial::create(['guest_name' => 'Three', 'content' => 'B', 'rating' => 3, 'is_active' => true]);

        $this->actingAs($this->admin())
            ->get(route('admin.testimonials.index', ['rating' => 5]))
            ->assertOk()
            ->assertSee('Five', false)
            ->assertDontSee('Three', false);
    }

    public function test_store_creates_testimonial_with_avatar(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.testimonials.store'), [
                'guest_name' => 'Juan',
                'content' => 'Amazing views.',
                'rating' => '5',
                'cottage_id' => (string) $this->cottage->id,
                'is_active' => '1',
                'guest_avatar' => UploadedFile::fake()->image('avatar.jpg', 20, 20),
            ])
            ->assertRedirect(route('admin.testimonials.index'));

        $testimonial = Testimonial::where('guest_name', 'Juan')->first();
        $this->assertNotNull($testimonial);
        $this->assertStringStartsWith('testimonials/', $testimonial->guest_avatar);
        $this->assertTrue(Storage::disk('cloudflare')->exists($testimonial->guest_avatar));
        $this->assertDatabaseHas('activity_logs', ['action' => 'testimonial.created', 'subject_id' => $testimonial->id]);
    }

    public function test_store_validates_rating_range(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.testimonials.store'), [
                'guest_name' => 'Bad',
                'content' => 'X',
                'rating' => '9',
            ])
            ->assertSessionHasErrors('rating');

        $this->assertDatabaseCount('testimonials', 0);
    }

    public function test_update_replaces_avatar_and_deletes_old(): void
    {
        $testimonial = Testimonial::create([
            'guest_name' => 'O', 'content' => 'C', 'rating' => 4,
            'guest_avatar' => 'testimonials/old.jpg', 'is_active' => true,
        ]);
        Storage::disk('cloudflare')->put('testimonials/old.jpg', 'OLD');

        $this->actingAs($this->admin())
            ->put(route('admin.testimonials.update', $testimonial), [
                'guest_name' => 'Updated',
                'content' => 'New',
                'rating' => '5',
                'is_active' => '1',
                'guest_avatar' => UploadedFile::fake()->image('new.jpg', 20, 20),
            ])
            ->assertRedirect(route('admin.testimonials.index'));

        $testimonial->refresh();
        $this->assertSame('Updated', $testimonial->guest_name);
        $this->assertStringStartsWith('testimonials/', $testimonial->guest_avatar);
        $this->assertFalse(Storage::disk('cloudflare')->exists('testimonials/old.jpg'));
        $this->assertDatabaseHas('activity_logs', ['action' => 'testimonial.updated', 'subject_id' => $testimonial->id]);
    }

    public function test_destroy_deletes_testimonial_and_avatar(): void
    {
        $testimonial = Testimonial::create([
            'guest_name' => 'Del', 'content' => 'C', 'rating' => 5,
            'guest_avatar' => 'testimonials/del.jpg', 'is_active' => true,
        ]);
        Storage::disk('cloudflare')->put('testimonials/del.jpg', 'BYE');

        $this->actingAs($this->admin())
            ->delete(route('admin.testimonials.destroy', $testimonial))
            ->assertRedirect(route('admin.testimonials.index'));

        $this->assertDatabaseMissing('testimonials', ['id' => $testimonial->id]);
        $this->assertFalse(Storage::disk('cloudflare')->exists('testimonials/del.jpg'));
        $this->assertDatabaseHas('activity_logs', ['action' => 'testimonial.deleted', 'subject_id' => $testimonial->id]);
    }

    public function test_staff_cannot_access_testimonials(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'staff']))
            ->get(route('admin.testimonials.index'))
            ->assertForbidden();
    }
}
