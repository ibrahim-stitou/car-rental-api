<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vehicle>
 */
class VehicleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $brands = [
            'Renault'  => ['Clio', 'Megane', 'Kadjar', 'Captur'],
            'Peugeot'  => ['208', '308', '3008', '5008'],
            'Dacia'    => ['Logan', 'Sandero', 'Duster', 'Jogger'],
            'Citroen'  => ['C3', 'C4', 'C5 Aircross', 'Berlingo'],
            'Hyundai'  => ['i10', 'i20', 'Tucson', 'Creta'],
            'Toyota'   => ['Yaris', 'Corolla', 'RAV4', 'Land Cruiser'],
            'Volkswagen' => ['Polo', 'Golf', 'Tiguan', 'T-Roc'],
        ];

        $brand = fake()->randomElement(array_keys($brands));
        $model = fake()->randomElement($brands[$brand]);

        return [
            'agency_id'           => Agency::factory(),
            'brand'               => $brand,
            'model'               => $model,
            'year'                => fake()->numberBetween(2018, 2026),
            'registration_number' => strtoupper(fake()->bothify('??-###-??')),
            'vin'                 => strtoupper(fake()->bothify('#################')),
            'color'               => fake()->randomElement(['Blanc', 'Noir', 'Gris', 'Rouge', 'Bleu', 'Argent']),
            'category'            => fake()->randomElement(['economy', 'compact', 'midsize', 'suv', 'luxury', 'van']),
            'fuel_type'           => fake()->randomElement(['gasoline', 'diesel', 'electric', 'hybrid']),
            'transmission'        => fake()->randomElement(['manual', 'automatic']),
            'seats'               => fake()->randomElement([2, 4, 5, 7]),
            'daily_rate'          => fake()->randomFloat(2, 200, 2000),
            'deposit_amount'      => fake()->randomFloat(2, 1000, 10000),
            'mileage'             => fake()->numberBetween(0, 200000),
            'status'              => 'available',
            'is_active'           => true,
        ];
    }
}
