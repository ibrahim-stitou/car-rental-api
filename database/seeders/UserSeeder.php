<?php

namespace Database\Seeders;

use App\Models\Agency;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $agency = Agency::first();

        // Super Admin
        $superAdmin = User::firstOrCreate(['email' => 'superadmin@ges-cars.ma'], [
            'first_name' => 'Super',
            'last_name'  => 'Admin',
            'password'   => 'password',
            'phone'      => '+212 600 000 001',
            'is_active'  => true,
        ]);
        $superAdmin->syncRoles([Role::findByName('super-admin', 'api')]);

        // Admin
        $admin = User::firstOrCreate(['email' => 'admin@ges-cars.ma'], [
            'agency_id'  => $agency?->id,
            'first_name' => 'Admin',
            'last_name'  => 'User',
            'password'   => 'password',
            'phone'      => '+212 600 000 002',
            'is_active'  => true,
        ]);
        $admin->syncRoles([Role::findByName('admin', 'api')]);

        // Agent
        $agent = User::firstOrCreate(['email' => 'agent@ges-cars.ma'], [
            'agency_id'  => $agency?->id,
            'first_name' => 'Agent',
            'last_name'  => 'User',
            'password'   => 'password',
            'phone'      => '+212 600 000 003',
            'is_active'  => true,
        ]);
        $agent->syncRoles([Role::findByName('agent', 'api')]);
    }
}
