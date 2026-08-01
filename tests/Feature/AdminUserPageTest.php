<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_users_index_returns_200_for_super_admin(): void
    {
        $user = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($user)
            ->get(route('admin.users.index'))
            ->assertStatus(200)
            ->assertSee('Total Users');
    }

    public function test_admin_users_index_renders_user_rows(): void
    {
        User::factory()->create(['name' => 'Super Admin', 'role' => 'super_admin']);
        User::factory()->create(['name' => 'Staff Person', 'role' => 'staff']);

        $admin = User::where('email', '!=', null)->where('role', 'super_admin')->first();

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertStatus(200)
            ->assertSee('Super Admin')
            ->assertSee('Staff Person');
    }

    public function test_staff_cannot_access_admin_users_index(): void
    {
        $user = User::factory()->create(['role' => 'staff']);

        $this->actingAs($user)
            ->get(route('admin.users.index'))
            ->assertStatus(403);
    }

    public function test_edit_buttons_pass_user_data_via_json_data_attribute(): void
    {
        $target = User::factory()->create(['name' => 'Editable User', 'email' => 'editable@helena.com', 'role' => 'admin']);
        $admin = User::factory()->create(['role' => 'super_admin']);

        $response = $this->actingAs($admin)->get(route('admin.users.index'));

        $response->assertStatus(200)
            ->assertDontSee('openEdit({', false)
            ->assertSee("JSON.parse(\$el.getAttribute('data-user'))", false);

        preg_match_all("/data-user='([^']*)'/", $response->getContent(), $matches);

        $this->assertNotEmpty($matches[1]);

        $found = collect($matches[1])
            ->map(fn ($json) => json_decode($json, true))
            ->first(fn ($user) => ($user['id'] ?? null) === $target->id);

        $this->assertNotNull($found);
        $this->assertSame($target->name, $found['name']);
        $this->assertSame($target->email, $found['email']);
        $this->assertSame($target->role, $found['role']);
    }

    public function test_ajax_request_returns_results_fragment_only(): void
    {
        $admin = User::factory()->create(['name' => 'Searchable Admin', 'role' => 'super_admin']);
        $admin2 = User::factory()->create(['name' => 'Another Person', 'role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.users.index') . '?search=Searchable', [
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->assertOk()
            ->assertSee('Searchable Admin')
            ->assertDontSee('Another Person')
            ->assertDontSee('<html', false);
    }

    public function test_search_filters_users_server_side(): void
    {
        User::factory()->create(['name' => 'Alpha Keeper', 'role' => 'admin']);
        User::factory()->create(['name' => 'Bravo Nobody', 'role' => 'staff']);

        $admin = User::where('role', 'admin')->first();

        $this->actingAs($admin)
            ->get(route('admin.users.index') . '?search=Alpha')
            ->assertOk()
            ->assertSee('Alpha Keeper')
            ->assertDontSee('Bravo Nobody');
    }
}
