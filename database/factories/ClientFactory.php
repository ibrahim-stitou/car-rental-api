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
            'client_type'               => 'physical',
            'first_name'                => fake()->firstName(),
            'last_name'                 => fake()->lastName(),
            'email'                     => fake()->unique()->safeEmail(),
            'phone'                     => fake()->phoneNumber(),
            'date_of_birth'             => fake()->dateTimeBetween('-60 years', '-20 years'),
            'nationality'               => 'MA',
            'id_type'                   => fake()->randomElement(['cin', 'passport', 'residence_permit']),
            'id_number'                 => strtoupper(fake()->bothify('??######')),
            'id_expiry_date'            => fake()->dateTimeBetween('+1 year', '+5 years'),
            'driving_license_number'    => strtoupper(fake()->bothify('##/######')),
            'driving_license_category'  => 'B',
            'driving_license_expiry'    => fake()->dateTimeBetween('+1 year', '+10 years'),
            'address'                   => fake()->address(),
            'city'                      => fake()->randomElement(['Casablanca', 'Rabat', 'Marrakech', 'Fès', 'Tanger']),
            'country'                   => 'MA',
            'is_blacklisted'            => false,
        ];
    }

    public function moral(): static
    {
        return $this->state(fn() => [
            'client_type'        => 'moral',
            'company_name'       => fake()->company(),
            'company_type'       => fake()->randomElement(['SARL', 'SA', 'SAS', 'SNC']),
            'company_phone'      => fake()->phoneNumber(),
            'company_email'      => fake()->unique()->companyEmail(),
            'company_ice'        => (string) fake()->numerify('##########'),
            'company_address'    => fake()->address(),
            'company_city'       => fake()->randomElement(['Casablanca', 'Rabat', 'Marrakech']),
            'company_country'    => 'MA',
            'bank_name'          => fake()->randomElement(['Attijariwafa Bank', 'BMCE', 'Banque Populaire', 'CIH Bank']),
            'bank_account_name'  => fake()->company(),
            'bank_account_number'=> strtoupper(fake()->bothify('RIBF###-#######-######')),
            'bank_address'       => fake()->address(),
            // Champs personne physique vide
            'first_name'               => null,
            'last_name'                => null,
            'date_of_birth'            => null,
            'nationality'              => null,
            'id_type'                  => null,
            'id_number'                => null,
            'id_expiry_date'           => null,
            'driving_license_number'   => null,
            'driving_license_category' => null,
            'driving_license_expiry'   => null,
            'birth_place'              => null,
        ]);
    }

    public function blacklisted(): static
    {
        return $this->state(fn() => [
            'is_blacklisted'  => true,
            'blacklist_reason' => fake()->sentence(),
        ]);
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Client $client) {
            if ($client->agencies()->count() === 0) {
                $client->agencies()->attach(Agency::factory()->create());
            }
        });
    }
}
