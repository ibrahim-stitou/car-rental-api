<?php

namespace Tests\Feature\Role;

use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PermissionTest extends TestCase
{
    // ─── INDEX ────────────────────────────────────────────────────────

    public function test_super_admin_can_list_permissions(): void
    {
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->getJson('/api/v1/permissions');

        $response->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_admin_cannot_list_permissions(): void
    {
        $user = $this->createAdmin();

        $response = $this->authAs($user)->getJson('/api/v1/permissions');

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_list_permissions(): void
    {
        $response = $this->getJson('/api/v1/permissions');

        $response->assertStatus(401);
    }

    // ─── STORE ────────────────────────────────────────────────────────

    public function test_super_admin_can_create_permission(): void
    {
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->postJson('/api/v1/permissions', [
            'name'       => 'custom-permission',
            'guard_name' => 'api',
        ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('permissions', ['name' => 'custom-permission']);
    }

    public function test_create_permission_fails_without_name(): void
    {
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->postJson('/api/v1/permissions', []);

        $response->assertStatus(422);
    }

    public function test_create_permission_fails_with_duplicate_name(): void
    {
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->postJson('/api/v1/permissions', [
            'name'       => 'view-agency',
            'guard_name' => 'api',
        ]);

        $response->assertStatus(422);
    }

    // ─── DELETE ───────────────────────────────────────────────────────

    public function test_super_admin_can_delete_custom_permission(): void
    {
        $permission = Permission::firstOrCreate(['name' => 'delete-me', 'guard_name' => 'api']);
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->deleteJson("/api/v1/permissions/{$permission->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('permissions', ['id' => $permission->id]);
    }

    public function test_delete_nonexistent_permission_returns_404(): void
    {
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->deleteJson('/api/v1/permissions/99999');

        $response->assertStatus(404);
    }
}

