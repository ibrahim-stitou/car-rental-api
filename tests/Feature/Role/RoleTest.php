<?php

namespace Tests\Feature\Role;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleTest extends TestCase
{
    // ─── INDEX ────────────────────────────────────────────────────────

    public function test_super_admin_can_list_roles(): void
    {
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->getJson('/api/v1/roles');

        $response->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_admin_cannot_list_roles(): void
    {
        $user = $this->createAdmin();

        $response = $this->authAs($user)->getJson('/api/v1/roles');

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_list_roles(): void
    {
        $response = $this->getJson('/api/v1/roles');

        $response->assertStatus(401);
    }

    // ─── STORE ────────────────────────────────────────────────────────

    public function test_super_admin_can_create_role(): void
    {
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->postJson('/api/v1/roles', [
            'name'        => 'custom-role',
            'guard_name'  => 'api',
            'permissions' => ['view-agency', 'view-vehicle'],
        ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('roles', ['name' => 'custom-role']);
    }

    public function test_create_role_fails_without_name(): void
    {
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->postJson('/api/v1/roles', []);

        $response->assertStatus(422);
    }

    public function test_create_role_fails_with_duplicate_name(): void
    {
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->postJson('/api/v1/roles', [
            'name'       => 'super-admin',
            'guard_name' => 'api',
        ]);

        $response->assertStatus(422);
    }

    // ─── SHOW ─────────────────────────────────────────────────────────

    public function test_super_admin_can_view_role(): void
    {
        $role = Role::findByName('admin', 'api');
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->getJson("/api/v1/roles/{$role->id}");

        $response->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_view_nonexistent_role_returns_404(): void
    {
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->getJson('/api/v1/roles/99999');

        $response->assertStatus(404);
    }

    // ─── UPDATE ───────────────────────────────────────────────────────

    public function test_super_admin_can_update_role(): void
    {
        $role = Role::firstOrCreate(['name' => 'test-role', 'guard_name' => 'api']);
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->putJson("/api/v1/roles/{$role->id}", [
            'name' => 'test-role-updated',
        ]);

        $response->assertOk()
            ->assertJson(['success' => true]);
    }

    // ─── DELETE ───────────────────────────────────────────────────────

    public function test_super_admin_can_delete_role(): void
    {
        $role = Role::firstOrCreate(['name' => 'role-to-delete', 'guard_name' => 'api']);
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->deleteJson("/api/v1/roles/{$role->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }

    // ─── ASSIGN PERMISSIONS ───────────────────────────────────────────

    public function test_super_admin_can_assign_permissions_to_role(): void
    {
        $role = Role::firstOrCreate(['name' => 'custom-perm-role', 'guard_name' => 'api']);
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->postJson("/api/v1/roles/{$role->id}/permissions", [
            'permissions' => ['view-agency', 'view-vehicle'],
        ]);

        $response->assertOk();
    }

    // ─── REVOKE PERMISSIONS ───────────────────────────────────────────

    public function test_super_admin_can_revoke_permissions_from_role(): void
    {
        $role = Role::firstOrCreate(['name' => 'revoke-role', 'guard_name' => 'api']);
        $role->givePermissionTo(Permission::findByName('view-agency', 'api'));
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->deleteJson("/api/v1/roles/{$role->id}/permissions", [
            'permissions' => ['view-agency'],
        ]);

        $response->assertOk();
    }
}

