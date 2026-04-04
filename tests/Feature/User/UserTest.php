<?php

namespace Tests\Feature\User;

use App\Models\Agency;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserTest extends TestCase
{
    // ─── INDEX ────────────────────────────────────────────────────────

    public function test_super_admin_can_list_users(): void
    {
        User::factory()->count(5)->create();
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->getJson('/api/v1/users');

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'success', 'message', 'data', 'meta' => ['current_page', 'total', 'per_page'],
            ]);
    }

    public function test_admin_can_list_users(): void
    {
        $user = $this->createAdmin();

        $response = $this->authAs($user)->getJson('/api/v1/users');

        $response->assertOk();
    }

    public function test_agent_cannot_list_users(): void
    {
        $user = $this->createAgent();

        $response = $this->authAs($user)->getJson('/api/v1/users');

        $response->assertStatus(403);
    }

    public function test_viewer_cannot_list_users(): void
    {
        $user = $this->createViewer();

        $response = $this->authAs($user)->getJson('/api/v1/users');

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_list_users(): void
    {
        $response = $this->getJson('/api/v1/users');

        $response->assertStatus(401);
    }

    public function test_list_users_with_pagination(): void
    {
        User::factory()->count(20)->create();
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->getJson('/api/v1/users?per_page=5');

        $response->assertOk()
            ->assertJsonPath('meta.per_page', 5);
    }

    // ─── STORE ────────────────────────────────────────────────────────

    public function test_super_admin_can_create_user(): void
    {
        $user = $this->createSuperAdmin();
        $agency = Agency::factory()->create();

        $data = [
            'agency_id'             => $agency->id,
            'first_name'            => 'Nouveau',
            'last_name'             => 'Utilisateur',
            'email'                 => 'nouveau@ges-cars.ma',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'phone'                 => '+212 600 555 666',
            'is_active'             => true,
        ];

        $response = $this->authAs($user)->postJson('/api/v1/users', $data);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('users', ['email' => 'nouveau@ges-cars.ma']);
    }

    public function test_admin_can_create_user(): void
    {
        $user = $this->createAdmin();
        $agency = Agency::factory()->create();

        $data = [
            'agency_id'             => $agency->id,
            'first_name'            => 'Agent',
            'last_name'             => 'Test',
            'email'                 => 'agent.test@ges-cars.ma',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'phone'                 => '+212 600 777 888',
            'is_active'             => true,
        ];

        $response = $this->authAs($user)->postJson('/api/v1/users', $data);

        $response->assertStatus(201);
    }

    public function test_create_user_fails_without_required_fields(): void
    {
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->postJson('/api/v1/users', []);

        $response->assertStatus(422);
    }

    public function test_create_user_fails_with_duplicate_email(): void
    {
        $user = $this->createSuperAdmin();
        $existing = User::factory()->create();

        $response = $this->authAs($user)->postJson('/api/v1/users', [
            'first_name'            => 'Dupli',
            'last_name'             => 'Cate',
            'email'                 => $existing->email,
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422);
    }

    // ─── SHOW ─────────────────────────────────────────────────────────

    public function test_super_admin_can_view_user(): void
    {
        $target = User::factory()->create();
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->getJson("/api/v1/users/{$target->id}");

        $response->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_view_nonexistent_user_returns_404(): void
    {
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->getJson('/api/v1/users/nonexistent-uuid');

        $response->assertStatus(404);
    }

    // ─── UPDATE ───────────────────────────────────────────────────────

    public function test_super_admin_can_update_user(): void
    {
        $target = User::factory()->create();
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->putJson("/api/v1/users/{$target->id}", [
            'first_name' => 'NomMisAJour',
            'last_name'  => 'PrenomMisAJour',
        ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('users', [
            'id'         => $target->id,
            'first_name' => 'NomMisAJour',
        ]);
    }

    // ─── DELETE ───────────────────────────────────────────────────────

    public function test_super_admin_can_delete_user(): void
    {
        $target = User::factory()->create();
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->deleteJson("/api/v1/users/{$target->id}");

        $response->assertOk();
        $this->assertSoftDeleted('users', ['id' => $target->id]);
    }

    // ─── TOGGLE ACTIVE ────────────────────────────────────────────────

    public function test_super_admin_can_toggle_user_active_status(): void
    {
        $target = User::factory()->create(['is_active' => true]);
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->patchJson("/api/v1/users/{$target->id}/toggle-active");

        $response->assertOk();
        $target->refresh();
        $this->assertFalse($target->is_active);
    }

    // ─── ASSIGN / REMOVE ROLE ─────────────────────────────────────────

    public function test_super_admin_can_assign_role_to_user(): void
    {
        $target = User::factory()->create();
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->postJson("/api/v1/users/{$target->id}/assign-role", [
            'role' => 'admin',
        ]);

        $response->assertOk();
        $this->assertTrue($target->fresh()->hasRole('admin'));
    }

    public function test_super_admin_can_remove_role_from_user(): void
    {
        $target = User::factory()->create();
        $target->assignRole(Role::findByName('admin', 'api'));
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->deleteJson("/api/v1/users/{$target->id}/remove-role", [
            'role' => 'admin',
        ]);

        $response->assertOk();
        $this->assertFalse($target->fresh()->hasRole('admin'));
    }

    // ─── PERMISSIONS ──────────────────────────────────────────────────

    public function test_super_admin_can_view_user_permissions(): void
    {
        $target = User::factory()->create();
        $target->assignRole(Role::findByName('admin', 'api'));
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->getJson("/api/v1/users/{$target->id}/permissions");

        $response->assertOk();
    }

    // ─── ACTIVITY LOGS ────────────────────────────────────────────────

    public function test_super_admin_can_view_user_activity_logs(): void
    {
        $target = User::factory()->create();
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->getJson("/api/v1/users/{$target->id}/activity-logs");

        $response->assertOk();
    }

    // ─── RESTORE ──────────────────────────────────────────────────────

    public function test_super_admin_can_restore_deleted_user(): void
    {
        $target = User::factory()->create();
        $target->delete();
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->postJson("/api/v1/users/{$target->id}/restore");

        $response->assertOk();
        $this->assertDatabaseHas('users', [
            'id'         => $target->id,
            'deleted_at' => null,
        ]);
    }
}

