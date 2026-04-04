<?php

namespace Database\Seeders;

use App\Models\Agency;
use Illuminate\Database\Seeder;

class AgencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Agency::firstOrCreate(['email' => 'casa@ges-cars.ma'], [
            'name'    => 'GES Cars Casablanca',
            'address' => '123 Boulevard Mohammed V, Casablanca',
            'city'    => 'Casablanca',
            'country' => 'MA',
            'phone'   => '+212 522 123 456',
        ]);

        Agency::firstOrCreate(['email' => 'marrakech@ges-cars.ma'], [
            'name'    => 'GES Cars Marrakech',
            'address' => '45 Avenue Mohammed VI, Guéliz',
            'city'    => 'Marrakech',
            'country' => 'MA',
            'phone'   => '+212 524 789 012',
        ]);
    }
}
