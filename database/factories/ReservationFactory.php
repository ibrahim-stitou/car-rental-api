<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\Client;
use App\Models\Reservation;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reservation>
 */
class ReservationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $pickupDate = fake()->dateTimeBetween('+1 day', '+30 days');
        $returnDate = fake()->dateTimeBetween($pickupDate, '+45 days');
        $days = max(1, (int) (new \DateTime($returnDate->format('Y-m-d')))->diff(new \DateTime($pickupDate->format('Y-m-d')))->days);
        $dailyRate = fake()->randomFloat(2, 200, 1500);
        $subtotal = $dailyRate * $days;

        return [
            'agency_id'           => Agency::factory(),
            'vehicle_id'          => Vehicle::factory(),
            'client_id'           => Client::factory(),
            'pickup_date'         => $pickupDate,
            'return_date'         => $returnDate,
            'pickup_location'     => fake()->randomElement(['Aéroport Mohammed V', 'Gare Casa Voyageurs', 'Agence Marrakech Centre']),
            'return_location'     => fake()->randomElement(['Aéroport Mohammed V', 'Gare Casa Voyageurs', 'Agence Marrakech Centre']),
            'status'              => fake()->randomElement(['pending', 'confirmed', 'active', 'completed', 'cancelled']),
            'daily_rate'          => $dailyRate,
            'total_days'          => $days,
            'subtotal'            => $subtotal,
            'discount_percentage' => 0,
            'discount_amount'     => 0,
            'additional_fees'     => 0,
            'total_amount'        => $subtotal,
            'deposit_amount'      => fake()->randomFloat(2, 500, 5000),
            'deposit_paid'        => fake()->boolean(70),
            'payment_status'      => fake()->randomElement(['pending', 'partial', 'paid']),
            'payment_method'      => fake()->randomElement(['cash', 'card', 'bank_transfer']),
            'fuel_level_pickup'   => 'full',
        ];
    }
}
