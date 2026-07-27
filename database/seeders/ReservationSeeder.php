<?php

namespace Database\Seeders;

use App\Models\Agency;
use App\Models\Client;
use App\Models\Reservation;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;

class ReservationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $agencies = Agency::all();

        foreach ($agencies as $agency) {
            $vehicles = Vehicle::where('agency_id', $agency->id)->get();
            $clients = Client::whereHas('agencies', fn ($q) => $q->where('agencies.id', $agency->id))->get();

            if ($vehicles->isEmpty() || $clients->isEmpty()) continue;

            for ($i = 0; $i < 7; $i++) {
                Reservation::factory()->create([
                    'agency_id'  => $agency->id,
                    'vehicle_id' => $vehicles->random()->id,
                    'client_id'  => $clients->random()->id,
                ]);
            }
        }
    }
}
