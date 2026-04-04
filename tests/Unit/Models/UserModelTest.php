<?php

namespace Tests\Unit\Models;

use App\Models\Agency;
use App\Models\User;
use Tests\TestCase;

class UserModelTest extends TestCase
{
    // ─── ACCESSORS ────────────────────────────────────────────────────

    public function test_full_name_accessor_returns_concatenated_name(): void
    {
        $user = User::factory()->make([
            'first_name' => 'Mohamed',
            'last_name'  => 'Alami',
        ]);

        $this->assertEquals('Mohamed Alami', $user->full_name);
    }

    // ─── UUID ─────────────────────────────────────────────────────────

    public function test_user_id_is_uuid(): void
    {
        $user = User::factory()->create();

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $user->id
        );
    }

    public function test_user_key_is_not_incrementing(): void
    {
        $user = new User();
        $this->assertFalse($user->getIncrementing());
        $this->assertEquals('string', $user->getKeyType());
    }

    public function test_two_users_have_different_uuids(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $this->assertNotEquals($user1->id, $user2->id);
    }

    // ─── SOFT DELETE ──────────────────────────────────────────────────

    public function test_user_is_soft_deleted(): void
    {
        $user = User::factory()->create();
        $user->delete();

        $this->assertSoftDeleted('users', ['id' => $user->id]);
        $this->assertNull(User::find($user->id));
        $this->assertNotNull(User::withTrashed()->find($user->id));
    }

    public function test_user_can_be_restored(): void
    {
        $user = User::factory()->create();
        $user->delete();
        $user->restore();

        $this->assertNotNull(User::find($user->id));
        $this->assertDatabaseHas('users', ['id' => $user->id, 'deleted_at' => null]);
    }

    // ─── CASTS ────────────────────────────────────────────────────────

    public function test_user_is_active_is_cast_to_boolean(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->assertIsBool($user->fresh()->is_active);
    }

    public function test_user_password_is_hidden(): void
    {
        $user = User::factory()->create();

        $array = $user->toArray();
        $this->assertArrayNotHasKey('password', $array);
        $this->assertArrayNotHasKey('remember_token', $array);
    }

    // ─── ROLES ────────────────────────────────────────────────────────

    public function test_user_can_have_role_assigned(): void
    {
        $user = User::factory()->create();
        $user->assignRole(\Spatie\Permission\Models\Role::findByName('admin', 'api'));

        $this->assertTrue($user->hasRole('admin'));
    }

    public function test_user_can_have_multiple_roles(): void
    {
        $user = User::factory()->create();
        $user->assignRole(\Spatie\Permission\Models\Role::findByName('admin', 'api'));
        $user->assignRole(\Spatie\Permission\Models\Role::findByName('viewer', 'api'));

        $this->assertTrue($user->hasRole('admin'));
        $this->assertTrue($user->hasRole('viewer'));
    }

    public function test_user_inherits_permissions_from_role(): void
    {
        $user = $this->createAdmin();

        $this->assertTrue($user->hasPermissionTo('view-agency', 'api'));
        $this->assertTrue($user->hasPermissionTo('view-vehicle', 'api'));
    }

    public function test_super_admin_has_all_permissions(): void
    {
        $user = $this->createSuperAdmin();

        $this->assertTrue($user->hasPermissionTo('create-role', 'api'));
        $this->assertTrue($user->hasPermissionTo('delete-role', 'api'));
        $this->assertTrue($user->hasPermissionTo('approve-billing', 'api'));
    }

    public function test_viewer_has_only_view_permissions(): void
    {
        $user = $this->createViewer();

        $this->assertTrue($user->hasPermissionTo('view-agency', 'api'));
        $this->assertFalse($user->hasPermissionTo('create-agency', 'api'));
        $this->assertFalse($user->hasPermissionTo('delete-agency', 'api'));
    }

    // ─── RELATIONS ────────────────────────────────────────────────────

    public function test_user_belongs_to_agency(): void
    {
        $agency = Agency::factory()->create();
        $user   = User::factory()->create(['agency_id' => $agency->id]);

        $this->assertInstanceOf(Agency::class, $user->agency);
        $this->assertEquals($agency->id, $user->agency->id);
    }

    public function test_user_without_agency_has_null_agency(): void
    {
        $user = User::factory()->create(['agency_id' => null]);

        $this->assertNull($user->agency);
    }

    // ─── JWT ──────────────────────────────────────────────────────────

    public function test_user_jwt_identifier_returns_primary_key(): void
    {
        $user = User::factory()->create();

        $this->assertEquals($user->id, $user->getJWTIdentifier());
    }

    public function test_user_jwt_custom_claims_is_empty_array(): void
    {
        $user = User::factory()->create();

        $this->assertIsArray($user->getJWTCustomClaims());
        $this->assertEmpty($user->getJWTCustomClaims());
    }
}

