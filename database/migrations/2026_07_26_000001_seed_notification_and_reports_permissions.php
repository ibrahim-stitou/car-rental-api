<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $catalog = [
            'Notifications' => [
                'view-notification' => 'Voir les notifications',
            ],
            'Rapports' => [
                'view-reports' => 'Voir les rapports',
            ],
        ];

        foreach ($catalog as $module => $permissions) {
            foreach ($permissions as $name => $label) {
                Permission::updateOrCreate(
                    ['name' => $name, 'guard_name' => 'api'],
                    ['label' => $label, 'module' => $module]
                );
            }
        }

        $superAdmin = Role::where('name', 'super-admin')->where('guard_name', 'api')->first();
        if ($superAdmin) {
            $superAdmin->syncPermissions(Permission::where('guard_name', 'api')->get());
        }

        // Admin keeps everything except role-management, mirroring RoleSeeder's rule.
        $admin = Role::where('name', 'admin')->where('guard_name', 'api')->first();
        if ($admin) {
            $admin->givePermissionTo(
                Permission::where('guard_name', 'api')
                    ->whereNotIn('name', ['create-role', 'edit-role', 'delete-role', 'assign-permission'])
                    ->get()
            );
        }

        $newGrants = [
            'manager' => ['view-notification', 'view-reports'],
            'agent'   => ['view-notification'],
            'viewer'  => ['view-notification'],
        ];
        foreach ($newGrants as $roleName => $permissionNames) {
            $role = Role::where('name', $roleName)->where('guard_name', 'api')->first();
            if ($role) {
                $role->givePermissionTo($permissionNames);
            }
        }
    }

    public function down(): void
    {
        Permission::whereIn('name', ['view-notification', 'view-reports'])->delete();
    }
};
