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

    public function test_page_embeds_users_for_client_side_filtering(): void
    {
        $target = User::factory()->create(['name' => 'Editable User', 'email' => 'editable@helena.com', 'role' => 'admin']);
        $admin = User::factory()->create(['name' => 'Root Admin', 'role' => 'super_admin']);

        $response = $this->actingAs($admin)->get(route('admin.users.index'));

        $response->assertStatus(200)
            ->assertDontSee('openEdit({', false)
            ->assertSee('x-for="user in filteredUsers"', false)
            ->assertSee('openEdit(user)', false);

        preg_match("/allUsers\s*:\s*JSON\.parse\('([^']*)'\)/", $response->getContent(), $matches);

        $this->assertArrayHasKey(1, $matches);

        $users = json_decode(json_decode('"' . $matches[1] . '"', true), true);

        $found = collect($users)->first(fn ($user) => ($user['id'] ?? null) === $target->id);

        $this->assertNotNull($found);
        $this->assertSame($target->name, $found['name']);
        $this->assertSame($target->email, $found['email']);
        $this->assertSame($target->role, $found['role']);
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

    public function test_admin_cannot_change_own_role(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $admin), [
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => 'super_admin',
                'password' => '',
            ])
            ->assertSessionHasErrors('role');

        $this->assertSame('admin', $admin->refresh()->role);
    }

    public function test_admin_cannot_modify_super_admin_record(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $superAdmin), [
                'name' => 'Hacked Name',
                'email' => $superAdmin->email,
                'role' => 'super_admin',
            ])
            ->assertSessionHasErrors('role');

        $this->assertSame($superAdmin->name, $superAdmin->refresh()->name);
    }

    public function test_admin_cannot_create_super_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'New SA',
                'email' => 'new-sa@helena.com',
                'password' => 'Password123',
                'role' => 'super_admin',
            ])
            ->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'new-sa@helena.com']);
    }

    public function test_super_admin_can_assign_super_admin_role(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($superAdmin)
            ->put(route('admin.users.update', $admin), [
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => 'super_admin',
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertSame('super_admin', $admin->refresh()->role);
    }

    public function test_admin_cannot_delete_super_admin_record(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $superAdmin))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $superAdmin->id]);
    }
}
