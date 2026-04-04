<?php

namespace Database\Seeders;

use App\Models\Agency;
use App\Models\Client;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $agencies = Agency::all();

        foreach ($agencies as $agency) {
            Client::factory()->count(10)->create(['agency_id' => $agency->id]);
        }
    }
}
