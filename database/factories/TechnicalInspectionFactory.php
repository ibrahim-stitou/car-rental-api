<?php

namespace Database\Factories;

use App\Models\TechnicalInspection;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TechnicalInspection>
 */
class TechnicalInspectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $inspectionDate = fake()->dateTimeBetween('-6 months', 'now');
        return [
            'vehicle_id'           => Vehicle::factory(),
            'inspection_date'      => $inspectionDate,
            'expiry_date'          => fake()->dateTimeBetween($inspectionDate, '+1 year'),
            'result'               => fake()->randomElement(['passed', 'failed', 'pending']),
            'inspection_center'    => fake()->randomElement(['Centre Narsa Casablanca', 'Centre Narsa Rabat', 'Centre Narsa Marrakech']),
            'inspector_name'       => fake()->name(),
            'observations'         => fake()->optional()->sentence(),
            'cost'                 => fake()->randomFloat(2, 200, 800),
            'next_inspection_date' => fake()->dateTimeBetween('+6 months', '+2 years'),
        ];
    }
}
