<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPostPageTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'super_admin']);
    }

    public function test_admin_can_create_post(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.posts.store'), [
                'title' => 'Summer Promo',
                'excerpt' => 'Save on long weekends.',
                'body' => '<p>Book now.</p>',
                'is_active' => '1',
                'published_at' => now()->format('Y-m-d\TH:i'),
            ])
            ->assertRedirect(route('admin.posts.index'));

        $this->assertDatabaseHas('posts', ['title' => 'Summer Promo', 'slug' => 'summer-promo']);

        $post = Post::where('slug', 'summer-promo')->first();
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'post.created',
            'subject_id' => $post->id,
        ]);
    }

    public function test_post_requires_title(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.posts.store'), ['title' => ''])
            ->assertSessionHasErrors('title');

        $this->assertDatabaseCount('posts', 0);
    }

    public function test_admin_can_update_post(): void
    {
        $post = Post::create(['title' => 'Original', 'published_at' => now()]);

        $this->actingAs($this->admin())
            ->put(route('admin.posts.update', $post), [
                'title' => 'Updated Title',
                'is_active' => '1',
                'published_at' => now()->format('Y-m-d\TH:i'),
            ])
            ->assertRedirect(route('admin.posts.index'));

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'title' => 'Updated Title',
            'slug' => $post->slug,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'post.updated',
            'subject_id' => $post->id,
        ]);
    }

    public function test_blank_published_at_marks_post_as_draft(): void
    {
        $post = Post::create(['title' => 'Original', 'published_at' => now()]);

        $this->actingAs($this->admin())
            ->put(route('admin.posts.update', $post), [
                'title' => 'Original',
                'is_active' => '1',
                'published_at' => '',
            ])
            ->assertRedirect(route('admin.posts.index'));

        $this->assertNull($post->fresh()->published_at);
        $this->assertDatabaseHas('activity_logs', ['action' => 'post.updated']);
    }

    public function test_admin_can_delete_post(): void
    {
        $post = Post::create(['title' => 'To Delete', 'published_at' => now()]);

        $this->actingAs($this->admin())
            ->delete(route('admin.posts.destroy', $post))
            ->assertRedirect(route('admin.posts.index'));

        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'post.deleted']);
    }

    public function test_staff_cannot_access_posts(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);

        $this->actingAs($staff)
            ->get(route('admin.posts.index'))
            ->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_access_posts(): void
    {
        $this->get(route('admin.posts.index'))->assertRedirect(route('admin.login'));
    }
}
