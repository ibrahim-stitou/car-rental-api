<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            AgencySeeder::class,
            UserSeeder::class,
            VehicleSeeder::class,
            ClientSeeder::class,
            ReservationSeeder::class,
            SettingsSeeder::class,
        ]);
    }
}
