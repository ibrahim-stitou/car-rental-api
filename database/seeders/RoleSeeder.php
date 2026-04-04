<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Super Admin — accès total
        $superAdmin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'api']);
        $superAdmin->givePermissionTo(Permission::where('guard_name', 'api')->get());

        // Admin — gestion complète d'une agence
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
        $admin->givePermissionTo(Permission::where('guard_name', 'api')
            ->whereNotIn('name', ['create-role', 'edit-role', 'delete-role', 'assign-permission'])
            ->get());

        // Manager — gestion opérationnelle
        $manager = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'api']);
        $manager->givePermissionTo([
            'view-agency',
            'view-vehicle', 'create-vehicle', 'edit-vehicle', 'manage-vehicle-documents',
            'view-technical-inspection', 'create-technical-inspection', 'edit-technical-inspection',
            'view-vignette', 'create-vignette', 'edit-vignette',
            'view-insurance', 'create-insurance', 'edit-insurance',
            'view-client', 'create-client', 'edit-client', 'blacklist-client',
            'view-reservation', 'create-reservation', 'edit-reservation',
            'confirm-reservation', 'activate-reservation', 'complete-reservation', 'cancel-reservation',
            'view-maintenance', 'create-maintenance', 'edit-maintenance',
            'view-billing', 'create-billing', 'edit-billing', 'approve-billing',
            'view-user', 'view-logs',
        ]);

        // Agent — création de réservations et gestion clients
        $agent = Role::firstOrCreate(['name' => 'agent', 'guard_name' => 'api']);
        $agent->givePermissionTo([
            'view-agency', 'view-vehicle',
            'view-client', 'create-client', 'edit-client',
            'view-reservation', 'create-reservation', 'edit-reservation',
            'confirm-reservation', 'activate-reservation', 'complete-reservation',
            'view-maintenance', 'view-insurance', 'view-vignette', 'view-technical-inspection',
        ]);

        // Viewer — lecture seule
        $viewer = Role::firstOrCreate(['name' => 'viewer', 'guard_name' => 'api']);
        $viewer->givePermissionTo([
            'view-agency', 'view-vehicle', 'view-client', 'view-reservation',
            'view-maintenance', 'view-insurance', 'view-vignette', 'view-technical-inspection',
        ]);
    }
}
