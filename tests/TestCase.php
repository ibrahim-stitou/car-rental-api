<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $admin;
    protected User $agent;
    protected User $viewer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPermissionsAndRoles();
    }

    protected function seedPermissionsAndRoles(): void
    {
        $permissions = [
            'view-agency', 'create-agency', 'edit-agency', 'delete-agency',
            'view-vehicle', 'create-vehicle', 'edit-vehicle', 'delete-vehicle', 'manage-vehicle-documents',
            'view-technical-inspection', 'create-technical-inspection', 'edit-technical-inspection', 'delete-technical-inspection',
            'view-vignette', 'create-vignette', 'edit-vignette', 'delete-vignette',
            'view-insurance', 'create-insurance', 'edit-insurance', 'delete-insurance',
            'view-client', 'create-client', 'edit-client', 'delete-client', 'blacklist-client',
            'view-reservation', 'create-reservation', 'edit-reservation', 'delete-reservation',
            'confirm-reservation', 'activate-reservation', 'complete-reservation', 'cancel-reservation',
            'view-maintenance', 'create-maintenance', 'edit-maintenance', 'delete-maintenance',
            'view-user', 'create-user', 'edit-user', 'delete-user', 'manage-user-roles',
            'view-role', 'create-role', 'edit-role', 'delete-role', 'assign-permission',
            'view-logs',
            'view-billing', 'create-billing', 'edit-billing', 'delete-billing', 'approve-billing',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'api']);
        }

        $superAdminRole = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'api']);
        $superAdminRole->givePermissionTo(Permission::where('guard_name', 'api')->get());

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
        $adminRole->givePermissionTo(Permission::where('guard_name', 'api')
            ->whereNotIn('name', ['create-role', 'edit-role', 'delete-role', 'assign-permission'])
            ->get());

        $agentRole = Role::firstOrCreate(['name' => 'agent', 'guard_name' => 'api']);
        $agentRole->givePermissionTo([
            'view-agency', 'view-vehicle',
            'view-client', 'create-client', 'edit-client',
            'view-reservation', 'create-reservation', 'edit-reservation',
            'confirm-reservation', 'activate-reservation', 'complete-reservation',
            'view-maintenance', 'view-insurance', 'view-vignette', 'view-technical-inspection',
        ]);

        $viewerRole = Role::firstOrCreate(['name' => 'viewer', 'guard_name' => 'api']);
        $viewerRole->givePermissionTo([
            'view-agency', 'view-vehicle', 'view-client', 'view-reservation',
            'view-maintenance', 'view-insurance', 'view-vignette', 'view-technical-inspection',
        ]);
    }

    protected function createSuperAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findByName('super-admin', 'api'));
        return $user;
    }

    protected function createAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findByName('admin', 'api'));
        return $user;
    }

    protected function createAgent(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findByName('agent', 'api'));
        return $user;
    }

    protected function createViewer(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findByName('viewer', 'api'));
        return $user;
    }

    protected function authAs(User $user): self
    {
        $token = auth('api')->login($user);
        return $this->withHeader('Authorization', "Bearer {$token}");
    }
}

