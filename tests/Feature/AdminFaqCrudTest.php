<?php

namespace Tests\Feature;

use App\Models\Faq;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 9.1 — the admin FaqController previously had no direct coverage.
 * Covers CRUD plus the bulk activate-all action and role gating.
 */
class AdminFaqCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'super_admin']);
    }

    public function test_index_renders_faqs(): void
    {
        Faq::create(['question' => 'Check-in time?', 'answer' => '3 PM', 'is_active' => true]);

        $this->actingAs($this->admin())
            ->get(route('admin.faqs.index'))
            ->assertOk()
            ->assertSee('Check-in time?', false);
    }

    public function test_create_and_edit_render(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->get(route('admin.faqs.create'))->assertOk();

        $faq = Faq::create(['question' => 'Q', 'answer' => 'A']);
        $this->actingAs($admin)->get(route('admin.faqs.edit', $faq))->assertOk();
    }

    public function test_store_creates_faq(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.faqs.store'), [
                'question' => 'Is parking free?',
                'answer' => 'Yes',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.faqs.index'));

        $faq = Faq::where('question', 'Is parking free?')->first();
        $this->assertNotNull($faq);
        $this->assertDatabaseHas('activity_logs', ['action' => 'faq.created', 'subject_id' => $faq->id]);
    }

    public function test_store_requires_question_and_answer(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.faqs.store'), ['question' => '', 'answer' => ''])
            ->assertSessionHasErrors(['question', 'answer']);

        $this->assertDatabaseCount('faqs', 0);
    }

    public function test_update_changes_faq(): void
    {
        $faq = Faq::create(['question' => 'Old Q', 'answer' => 'Old A', 'is_active' => false]);

        $this->actingAs($this->admin())
            ->put(route('admin.faqs.update', $faq), [
                'question' => 'New Q',
                'answer' => 'New A',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.faqs.index'));

        $faq->refresh();
        $this->assertSame('New Q', $faq->question);
        $this->assertTrue($faq->is_active);
        $this->assertDatabaseHas('activity_logs', ['action' => 'faq.updated', 'subject_id' => $faq->id]);
    }

    public function test_destroy_deletes_faq(): void
    {
        $faq = Faq::create(['question' => 'Q', 'answer' => 'A']);

        $this->actingAs($this->admin())
            ->delete(route('admin.faqs.destroy', $faq))
            ->assertRedirect(route('admin.faqs.index'));

        $this->assertDatabaseMissing('faqs', ['id' => $faq->id]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'faq.deleted', 'subject_id' => $faq->id]);
    }

    public function test_activate_all_activates_every_faq(): void
    {
        Faq::create(['question' => 'A', 'answer' => 'A', 'is_active' => false]);
        Faq::create(['question' => 'B', 'answer' => 'B', 'is_active' => false]);

        $this->actingAs($this->admin())
            ->post(route('admin.faqs.activate-all'))
            ->assertRedirect(route('admin.faqs.index'));

        $this->assertSame(0, Faq::where('is_active', false)->count());
        $this->assertDatabaseHas('activity_logs', ['action' => 'faq.activated', 'subject_id' => null]);
    }

    public function test_staff_cannot_access_faqs(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'staff']))
            ->get(route('admin.faqs.index'))
            ->assertForbidden();
    }
}
