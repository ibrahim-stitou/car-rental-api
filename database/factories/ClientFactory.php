<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'agency_id'                => Agency::factory(),
            'first_name'               => fake()->firstName(),
            'last_name'                => fake()->lastName(),
            'email'                    => fake()->unique()->safeEmail(),
            'phone'                    => fake()->phoneNumber(),
            'date_of_birth'            => fake()->dateTimeBetween('-60 years', '-20 years'),
            'nationality'              => 'MA',
            'id_type'                  => fake()->randomElement(['cin', 'passport', 'residence_permit']),
            'id_number'                => strtoupper(fake()->bothify('??######')),
            'id_expiry_date'           => fake()->dateTimeBetween('+1 year', '+5 years'),
            'driving_license_number'   => strtoupper(fake()->bothify('##/######')),
            'driving_license_category' => 'B',
            'driving_license_expiry'   => fake()->dateTimeBetween('+1 year', '+10 years'),
            'address'                  => fake()->address(),
            'city'                     => fake()->randomElement(['Casablanca', 'Rabat', 'Marrakech', 'Fès', 'Tanger']),
            'country'                  => 'MA',
            'is_blacklisted'           => false,
        ];
    }

    public function blacklisted(): static
    {
        return $this->state(fn() => [
            'is_blacklisted'  => true,
            'blacklist_reason' => fake()->sentence(),
        ]);
    }
}
