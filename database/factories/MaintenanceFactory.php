<?php

namespace Database\Factories;

use App\Models\Maintenance;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Maintenance>
 */
class MaintenanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vehicle_id'           => Vehicle::factory(),
            'type'                 => fake()->randomElement(['oil_change', 'tire_change', 'brake_service', 'engine_repair', 'body_repair', 'electrical', 'cleaning', 'other']),
            'description'          => fake()->sentence(10),
            'maintenance_date'     => fake()->dateTimeBetween('-3 months', '+1 month'),
            'completion_date'      => fake()->optional(0.5)->dateTimeBetween('now', '+2 months'),
            'mileage_at_service'   => fake()->numberBetween(10000, 200000),
            'next_service_mileage' => fake()->numberBetween(210000, 300000),
            'next_service_date'    => fake()->dateTimeBetween('+3 months', '+1 year'),
            'cost'                 => fake()->randomFloat(2, 200, 15000),
            'service_provider'     => fake()->randomElement(['Garage Atlas', 'Auto Service Marrakech', 'Renault Service Casa', 'Garage Central Rabat']),
            'status'               => fake()->randomElement(['scheduled', 'in_progress', 'completed', 'cancelled']),
            'priority'             => fake()->randomElement(['low', 'medium', 'high', 'urgent']),
        ];
    }
}
