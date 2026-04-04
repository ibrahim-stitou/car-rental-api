<?php

namespace Database\Factories;

use App\Models\Vehicle;
use App\Models\Vignette;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vignette>
 */
class VignetteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $year = fake()->numberBetween(2024, 2026);
        return [
            'vehicle_id'        => Vehicle::factory(),
            'year'              => $year,
            'issue_date'        => "{$year}-01-01",
            'expiry_date'       => "{$year}-12-31",
            'amount'            => fake()->randomFloat(2, 500, 5000),
            'payment_method'    => fake()->randomElement(['cash', 'bank_transfer', 'online']),
            'payment_reference' => strtoupper(fake()->bothify('VIG-####-????')),
            'is_paid'           => fake()->boolean(80),
            'paid_at'           => fake()->optional(0.8)->dateTimeThisYear(),
        ];
    }
}
