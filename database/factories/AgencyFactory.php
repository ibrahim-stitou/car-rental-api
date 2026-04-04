<?php

namespace Database\Factories;

use App\Models\Agency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Agency>
 */
class AgencyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'      => fake()->company(),
            'address'   => fake()->address(),
            'city'      => fake()->randomElement(['Casablanca', 'Rabat', 'Marrakech', 'Fès', 'Tanger', 'Agadir']),
            'country'   => 'MA',
            'phone'     => fake()->phoneNumber(),
            'email'     => fake()->unique()->companyEmail(),
            'is_active' => true,
        ];
    }
}
