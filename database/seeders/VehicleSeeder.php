<?php

namespace Database\Seeders;

use App\Models\Agency;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;

class VehicleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $agencies = Agency::all();

        foreach ($agencies as $agency) {
            Vehicle::factory()->count(5)->create(['agency_id' => $agency->id]);
        }
    }
}
