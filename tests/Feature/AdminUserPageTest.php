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
}
