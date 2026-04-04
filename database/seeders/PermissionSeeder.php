<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // Agencies
            'view-agency', 'create-agency', 'edit-agency', 'delete-agency',
            // Vehicles
            'view-vehicle', 'create-vehicle', 'edit-vehicle', 'delete-vehicle', 'manage-vehicle-documents',
            // Technical Inspections
            'view-technical-inspection', 'create-technical-inspection', 'edit-technical-inspection', 'delete-technical-inspection',
            // Vignettes
            'view-vignette', 'create-vignette', 'edit-vignette', 'delete-vignette',
            // Insurances
            'view-insurance', 'create-insurance', 'edit-insurance', 'delete-insurance',
            // Clients
            'view-client', 'create-client', 'edit-client', 'delete-client', 'blacklist-client',
            // Reservations
            'view-reservation', 'create-reservation', 'edit-reservation', 'delete-reservation',
            'confirm-reservation', 'activate-reservation', 'complete-reservation', 'cancel-reservation',
            // Maintenance
            'view-maintenance', 'create-maintenance', 'edit-maintenance', 'delete-maintenance',
            // Users
            'view-user', 'create-user', 'edit-user', 'delete-user', 'manage-user-roles',
            // Roles
            'view-role', 'create-role', 'edit-role', 'delete-role', 'assign-permission',
            // Logs
            'view-logs',
            // Billing
            'view-billing', 'create-billing', 'edit-billing', 'delete-billing', 'approve-billing',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'api']);
        }
    }
}
