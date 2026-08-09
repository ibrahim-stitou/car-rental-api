<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $catalog = [
            'Réservations' => [
                'manage-contract' => 'Dévalider / régénérer le contrat',
                'manage-payment'  => 'Ajouter / supprimer un paiement (hors acompte initial)',
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

        // Deliberately not granted to manager/agent/viewer by default — these two
        // permissions exist specifically to restrict who can touch a contract's
        // validity or record payments beyond the initial deposit, so an explicit
        // grant (via the Role management screen) is required for any role besides
        // super-admin/admin.
    }

    public function down(): void
    {
        Permission::whereIn('name', ['manage-contract', 'manage-payment'])->delete();
    }
};
