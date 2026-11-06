<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        return User::factory()->role(User::ROLE_SUPER_ADMIN)->create();
    }

    private function admin(): User
    {
        return User::factory()->role(User::ROLE_ADMIN)->create();
    }

    private function profile(User $user, array $overrides = []): array
    {
        return array_merge([
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'is_active' => '1',
        ], $overrides);
    }

    public function test_admin_cannot_reset_super_admin_password(): void
    {
        $super = $this->superAdmin();
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.users.reset-password', $super), [
            'password' => 'nouveau-mdp-123',
            'password_confirmation' => 'nouveau-mdp-123',
        ])->assertForbidden();

        $this->assertFalse(Hash::check('nouveau-mdp-123', $super->fresh()->password));
    }

    public function test_admin_cannot_edit_disable_or_delete_super_admin(): void
    {
        $super = $this->superAdmin();
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.users.edit', $super))->assertForbidden();
        $this->actingAs($admin)->put(route('admin.users.update', $super), $this->profile($super, ['role' => User::ROLE_CLIENT]))->assertForbidden();
        $this->actingAs($admin)->post(route('admin.users.toggle', $super))->assertForbidden();
        $this->actingAs($admin)->delete(route('admin.users.destroy', $super))->assertForbidden();

        $super->refresh();
        $this->assertSame(User::ROLE_SUPER_ADMIN, $super->role);
        $this->assertTrue($super->is_active);
    }

    public function test_super_admin_can_save_own_profile_without_losing_role(): void
    {
        $super = $this->superAdmin();

        $this->actingAs($super)
            ->put(route('admin.users.update', $super), $this->profile($super, ['name' => 'Direction']))
            ->assertRedirect(route('admin.users.index'));

        $super->refresh();
        $this->assertSame('Direction', $super->name);
        $this->assertSame(User::ROLE_SUPER_ADMIN, $super->role);
    }

    public function test_user_cannot_change_own_role_or_disable_self(): void
    {
        // Deux autres administrateurs : la règle « dernier admin » ne joue pas.
        $this->superAdmin();
        $this->admin();
        $admin = $this->admin();

        $this->actingAs($admin)->put(
            route('admin.users.update', $admin),
            $this->profile($admin, ['role' => User::ROLE_AGENT, 'is_active' => '0'])
        );

        $admin->refresh();
        $this->assertSame(User::ROLE_ADMIN, $admin->role);
        $this->assertTrue($admin->is_active);

        $this->actingAs($admin)->post(route('admin.users.toggle', $admin))->assertSessionHasErrors('error');
        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_admin_can_still_manage_regular_accounts(): void
    {
        $admin = $this->admin();
        $agent = User::factory()->role(User::ROLE_AGENT)->create();

        $this->actingAs($admin)->post(route('admin.users.toggle', $agent));
        $this->assertFalse($agent->fresh()->is_active);
    }

    public function test_admin_cannot_grant_super_admin_role(): void
    {
        $admin = $this->admin();
        $agent = User::factory()->role(User::ROLE_AGENT)->create();

        $this->actingAs($admin)
            ->put(route('admin.users.update', $agent), $this->profile($agent, ['role' => User::ROLE_SUPER_ADMIN]))
            ->assertSessionHasErrors('role');

        $this->assertSame(User::ROLE_AGENT, $agent->fresh()->role);
    }
}
