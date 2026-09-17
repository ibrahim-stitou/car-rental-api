<?php

namespace Tests\Unit\Models;

use App\Models\Agency;
use App\Models\Client;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Vehicle;
use Tests\TestCase;

class ReservationModelTest extends TestCase
{
    // ─── RESERVATION NUMBER ───────────────────────────────────────────

    public function test_reservation_number_is_auto_generated_on_creation(): void
    {
        $agency  = Agency::factory()->create();
        $vehicle = Vehicle::factory()->create(['agency_id' => $agency->id]);
        $client  = Client::factory()->create(['agency_id' => $agency->id]);

        $reservation = Reservation::factory()->create([
            'agency_id'  => $agency->id,
            'vehicle_id' => $vehicle->id,
            'client_id'  => $client->id,
        ]);

        $this->assertNotNull($reservation->reservation_number);
        $this->assertStringStartsWith('RES-', $reservation->reservation_number);
    }

    public function test_reservation_number_format_is_correct(): void
    {
        $agency  = Agency::factory()->create();
        $vehicle = Vehicle::factory()->create(['agency_id' => $agency->id]);
        $client  = Client::factory()->create(['agency_id' => $agency->id]);

        $reservation = Reservation::factory()->create([
            'agency_id'  => $agency->id,
            'vehicle_id' => $vehicle->id,
            'client_id'  => $client->id,
        ]);

        $this->assertMatchesRegularExpression('/^RES-\d{6}$/', $reservation->reservation_number);
    }

    // ─── CALCULATE TOTAL ──────────────────────────────────────────────

    public function test_calculate_total_computes_correct_values(): void
    {
        $agency  = Agency::factory()->create();
        $vehicle = Vehicle::factory()->create(['agency_id' => $agency->id]);
        $client  = Client::factory()->create(['agency_id' => $agency->id]);

        $reservation = Reservation::factory()->make([
            'agency_id'          => $agency->id,
            'vehicle_id'         => $vehicle->id,
            'client_id'          => $client->id,
            'pickup_date'        => now(),
            'return_date'        => now()->addDays(5),
            'daily_rate'         => 500.00,
            'discount_percentage'=> 10,
            'additional_fees'    => 200,
        ]);

        $reservation->calculateTotal();

        $this->assertEquals(5, $reservation->total_days);
        $this->assertEquals(2500.00, $reservation->subtotal);
        $this->assertEquals(250.00, $reservation->discount_amount);
        $this->assertEquals(2450.00, $reservation->total_amount); // 2500 - 250 + 200
    }

    public function test_calculate_total_minimum_one_day(): void
    {
        $agency  = Agency::factory()->create();
        $vehicle = Vehicle::factory()->create(['agency_id' => $agency->id]);
        $client  = Client::factory()->create(['agency_id' => $agency->id]);

        $reservation = Reservation::factory()->make([
            'agency_id'          => $agency->id,
            'vehicle_id'         => $vehicle->id,
            'client_id'          => $client->id,
            'pickup_date'        => now(),
            'return_date'        => now(), // même jour
            'daily_rate'         => 300.00,
            'discount_percentage'=> 0,
            'additional_fees'    => 0,
        ]);

        $reservation->calculateTotal();

        $this->assertEquals(1, $reservation->total_days); // minimum 1 jour
    }

    // ─── IS OVERDUE ───────────────────────────────────────────────────

    public function test_is_overdue_returns_true_for_active_past_reservation(): void
    {
        $agency  = Agency::factory()->create();
        $vehicle = Vehicle::factory()->create(['agency_id' => $agency->id]);
        $client  = Client::factory()->create(['agency_id' => $agency->id]);

        $reservation = Reservation::factory()->create([
            'agency_id'  => $agency->id,
            'vehicle_id' => $vehicle->id,
            'client_id'  => $client->id,
            'status'     => 'active',
            'pickup_date'=> now()->subDays(5),
            'return_date'=> now()->subDay(),
        ]);

        $this->assertTrue($reservation->isOverdue());
    }

    public function test_is_overdue_returns_false_for_future_reservation(): void
    {
        $agency  = Agency::factory()->create();
        $vehicle = Vehicle::factory()->create(['agency_id' => $agency->id]);
        $client  = Client::factory()->create(['agency_id' => $agency->id]);

        $reservation = Reservation::factory()->create([
            'agency_id'  => $agency->id,
            'vehicle_id' => $vehicle->id,
            'client_id'  => $client->id,
            'status'     => 'active',
            'pickup_date'=> now()->addDay(),
            'return_date'=> now()->addDays(5),
        ]);

        $this->assertFalse($reservation->isOverdue());
    }

    public function test_is_overdue_returns_false_for_completed_reservation(): void
    {
        $agency  = Agency::factory()->create();
        $vehicle = Vehicle::factory()->create(['agency_id' => $agency->id]);
        $client  = Client::factory()->create(['agency_id' => $agency->id]);

        $reservation = Reservation::factory()->create([
            'agency_id'  => $agency->id,
            'vehicle_id' => $vehicle->id,
            'client_id'  => $client->id,
            'status'     => 'completed',
            'pickup_date'=> now()->subDays(5),
            'return_date'=> now()->subDay(),
        ]);

        $this->assertFalse($reservation->isOverdue());
    }

    // ─── SCOPES ───────────────────────────────────────────────────────

    public function test_scope_overdue_returns_only_overdue_reservations(): void
    {
        $agency  = Agency::factory()->create();
        $vehicle = Vehicle::factory()->create(['agency_id' => $agency->id]);
        $client  = Client::factory()->create(['agency_id' => $agency->id]);

        // Réservation en retard
        Reservation::factory()->create([
            'agency_id'  => $agency->id,
            'vehicle_id' => $vehicle->id,
            'client_id'  => $client->id,
            'status'     => 'active',
            'pickup_date'=> now()->subDays(5),
            'return_date'=> now()->subDay(),
        ]);

        // Réservation non en retard
        Reservation::factory()->create([
            'agency_id'  => $agency->id,
            'vehicle_id' => $vehicle->id,
            'client_id'  => $client->id,
            'status'     => 'active',
            'pickup_date'=> now()->addDay(),
            'return_date'=> now()->addDays(5),
        ]);

        $overdue = Reservation::overdue()->get();
        $this->assertEquals(1, $overdue->count());
    }

    public function test_scope_by_status_filters_correctly(): void
    {
        $agency  = Agency::factory()->create();
        $vehicle = Vehicle::factory()->create(['agency_id' => $agency->id]);
        $client  = Client::factory()->create(['agency_id' => $agency->id]);

        Reservation::factory()->count(3)->create([
            'agency_id'  => $agency->id,
            'vehicle_id' => $vehicle->id,
            'client_id'  => $client->id,
            'status'     => 'pending',
        ]);
        Reservation::factory()->count(2)->create([
            'agency_id'  => $agency->id,
            'vehicle_id' => $vehicle->id,
            'client_id'  => $client->id,
            'status'     => 'confirmed',
        ]);

        $this->assertEquals(3, Reservation::byStatus('pending')->count());
        $this->assertEquals(2, Reservation::byStatus('confirmed')->count());
    }

    // ─── RELATIONS ────────────────────────────────────────────────────

    public function test_reservation_belongs_to_agency(): void
    {
        $agency  = Agency::factory()->create();
        $vehicle = Vehicle::factory()->create(['agency_id' => $agency->id]);
        $client  = Client::factory()->create(['agency_id' => $agency->id]);

        $reservation = Reservation::factory()->create([
            'agency_id'  => $agency->id,
            'vehicle_id' => $vehicle->id,
            'client_id'  => $client->id,
        ]);

        $this->assertInstanceOf(Agency::class, $reservation->agency);
        $this->assertEquals($agency->id, $reservation->agency->id);
    }

    public function test_reservation_belongs_to_vehicle(): void
    {
        $agency  = Agency::factory()->create();
        $vehicle = Vehicle::factory()->create(['agency_id' => $agency->id]);
        $client  = Client::factory()->create(['agency_id' => $agency->id]);

        $reservation = Reservation::factory()->create([
            'agency_id'  => $agency->id,
            'vehicle_id' => $vehicle->id,
            'client_id'  => $client->id,
        ]);

        $this->assertInstanceOf(Vehicle::class, $reservation->vehicle);
        $this->assertEquals($vehicle->id, $reservation->vehicle->id);
    }

    public function test_reservation_belongs_to_client(): void
    {
        $agency  = Agency::factory()->create();
        $vehicle = Vehicle::factory()->create(['agency_id' => $agency->id]);
        $client  = Client::factory()->create(['agency_id' => $agency->id]);

        $reservation = Reservation::factory()->create([
            'agency_id'  => $agency->id,
            'vehicle_id' => $vehicle->id,
            'client_id'  => $client->id,
        ]);

        $this->assertInstanceOf(Client::class, $reservation->client);
        $this->assertEquals($client->id, $reservation->client->id);
    }

    // ─── LLD / PAIEMENT EN FIN DE MOIS ────────────────────────────────

    public function test_lld_owes_zero_months_at_signing(): void
    {
        $agency  = Agency::factory()->create();
        $vehicle = Vehicle::factory()->create(['agency_id' => $agency->id]);
        $client  = Client::factory()->create(['agency_id' => $agency->id]);

        $reservation = Reservation::factory()->make([
            'agency_id'    => $agency->id,
            'vehicle_id'   => $vehicle->id,
            'client_id'    => $client->id,
            'rental_unit'  => 'month',
            'monthly_rate' => 4000,
            'total_months' => 24,
            'pickup_date'  => now()->subWeek(),   // moins d'un mois complet écoulé
            'return_date'  => now()->addMonths(23),
        ]);

        // Le 1er mois n'est dû qu'à la fin du 1er mois — rien n'est dû ici.
        $this->assertSame(0, $reservation->months_due);
        $this->assertSame(0.0, $reservation->amount_due_so_far);
    }

    public function test_lld_owes_one_month_after_first_month_end(): void
    {
        $agency  = Agency::factory()->create();
        $vehicle = Vehicle::factory()->create(['agency_id' => $agency->id]);
        $client  = Client::factory()->create(['agency_id' => $agency->id]);

        $reservation = Reservation::factory()->make([
            'agency_id'    => $agency->id,
            'vehicle_id'   => $vehicle->id,
            'client_id'    => $client->id,
            'rental_unit'  => 'month',
            'monthly_rate' => 4000,
            'total_months' => 24,
            'pickup_date'  => now()->subMonths(1)->subDay(),
            'return_date'  => now()->addMonths(22),
        ]);

        $this->assertSame(1, $reservation->months_due);
        $this->assertSame(4000.0, $reservation->amount_due_so_far);
    }

    public function test_lld_months_due_capped_at_contract_length(): void
    {
        $agency  = Agency::factory()->create();
        $vehicle = Vehicle::factory()->create(['agency_id' => $agency->id]);
        $client  = Client::factory()->create(['agency_id' => $agency->id]);

        $reservation = Reservation::factory()->make([
            'agency_id'    => $agency->id,
            'vehicle_id'   => $vehicle->id,
            'client_id'    => $client->id,
            'rental_unit'  => 'month',
            'monthly_rate' => 4000,
            'total_months' => 3,
            'pickup_date'  => now()->subMonths(4),
            'return_date'  => now()->subMonth(),
        ]);

        $this->assertSame(3, $reservation->months_due);
        $this->assertSame(12000.0, $reservation->amount_due_so_far);
    }
}

