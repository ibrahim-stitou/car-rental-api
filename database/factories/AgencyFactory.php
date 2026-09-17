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
            'legal_form'=> fake()->randomElement(['SARL', 'SA', 'SAS']),
            'capital'   => (string) fake()->numberBetween(100000, 5000000),
            'rc'        => (string) fake()->numerify('######'),
            'tax_id'    => (string) fake()->numerify('#######'),
            'patente'   => (string) fake()->numerify('########'),
            'ice'       => (string) fake()->numerify('################'),
            'bank_name'      => fake()->randomElement(['Attijariwafa Bank', 'BMCE', 'Banque Populaire', 'CIH Bank']),
            'bank_branch'    => fake()->city() . ' — Agence principale',
            'bank_address'   => fake()->address(),
            'bank_account'   => (string) fake()->numerify('0##################'),
            'bank_rib'       => strtoupper(fake()->bothify('MA64##############?##############')),
        ];
    }
}
