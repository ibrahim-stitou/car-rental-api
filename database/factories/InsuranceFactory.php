<?php

namespace Database\Factories;

use App\Models\Insurance;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Insurance>
 */
class InsuranceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('-6 months', 'now');
        return [
            'vehicle_id'        => Vehicle::factory(),
            'insurance_company' => fake()->randomElement(['Wafa Assurance', 'RMA', 'Saham Assurance', 'AXA Maroc', 'Atlanta']),
            'policy_number'     => strtoupper(fake()->bothify('POL-####-????-##')),
            'type'              => fake()->randomElement(['third_party', 'comprehensive', 'all_risk']),
            'start_date'        => $startDate,
            'end_date'          => fake()->dateTimeBetween($startDate, '+1 year'),
            'premium_amount'    => fake()->randomFloat(2, 2000, 15000),
            'deductible_amount' => fake()->randomFloat(2, 500, 5000),
            'coverage_details'  => ['windshield' => true, 'theft' => true, 'fire' => true],
            'is_active'         => true,
            'agent_name'        => fake()->name(),
            'agent_phone'       => fake()->phoneNumber(),
        ];
    }
}
