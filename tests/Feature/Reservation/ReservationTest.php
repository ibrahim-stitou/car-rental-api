<?php

namespace Tests\Feature\Reservation;

use App\Models\Agency;
use App\Models\Client;
use App\Models\Reservation;
use App\Models\Vehicle;
use Tests\TestCase;

class ReservationTest extends TestCase
{
    private Agency $agency;
    private Vehicle $vehicle;
    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agency  = Agency::factory()->create();
        $this->vehicle = Vehicle::factory()->create(['agency_id' => $this->agency->id, 'status' => 'available']);
        $this->client  = Client::factory()->create(['agency_id' => $this->agency->id]);
    }

    // ─── INDEX ────────────────────────────────────────────────────────

    public function test_authenticated_user_can_list_reservations(): void
    {
        Reservation::factory()->count(5)->create([
            'agency_id'  => $this->agency->id,
            'vehicle_id' => $this->vehicle->id,
            'client_id'  => $this->client->id,
        ]);
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->getJson('/api/v1/reservations');

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'success', 'message', 'data', 'meta' => ['current_page', 'total', 'per_page'],
            ]);
    }

    public function test_unauthenticated_user_cannot_list_reservations(): void
    {
        $response = $this->getJson('/api/v1/reservations');

        $response->assertStatus(401);
    }

    public function test_list_reservations_with_pagination(): void
    {
        Reservation::factory()->count(20)->create([
            'agency_id'  => $this->agency->id,
            'vehicle_id' => $this->vehicle->id,
            'client_id'  => $this->client->id,
        ]);
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->getJson('/api/v1/reservations?per_page=5');

        $response->assertOk()
            ->assertJsonPath('meta.per_page', 5);
    }

    public function test_list_reservations_filter_by_status(): void
    {
        Reservation::factory()->create([
            'agency_id' => $this->agency->id, 'vehicle_id' => $this->vehicle->id,
            'client_id' => $this->client->id, 'status' => 'pending',
        ]);
        Reservation::factory()->create([
            'agency_id' => $this->agency->id, 'vehicle_id' => $this->vehicle->id,
            'client_id' => $this->client->id, 'status' => 'confirmed',
        ]);
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->getJson('/api/v1/reservations?status=pending');

        $response->assertOk();
    }

    public function test_list_reservations_filter_by_vehicle(): void
    {
        Reservation::factory()->count(3)->create([
            'agency_id' => $this->agency->id, 'vehicle_id' => $this->vehicle->id,
            'client_id' => $this->client->id,
        ]);
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->getJson("/api/v1/reservations?vehicle_id={$this->vehicle->id}");

        $response->assertOk();
    }

    public function test_list_reservations_filter_by_client(): void
    {
        Reservation::factory()->count(2)->create([
            'agency_id' => $this->agency->id, 'vehicle_id' => $this->vehicle->id,
            'client_id' => $this->client->id,
        ]);
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->getJson("/api/v1/reservations?client_id={$this->client->id}");

        $response->assertOk();
    }

    public function test_list_reservations_with_search(): void
    {
        Reservation::factory()->create([
            'agency_id' => $this->agency->id, 'vehicle_id' => $this->vehicle->id,
            'client_id' => $this->client->id,
        ]);
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->getJson('/api/v1/reservations?search=RES');

        $response->assertOk();
    }

    // ─── STORE ────────────────────────────────────────────────────────

    public function test_user_with_permission_can_create_reservation(): void
    {
        $user = $this->createSuperAdmin();

        $data = [
            'agency_id'       => $this->agency->id,
            'vehicle_id'      => $this->vehicle->id,
            'client_id'       => $this->client->id,
            'pickup_date'     => now()->addDays(1)->toDateTimeString(),
            'return_date'     => now()->addDays(5)->toDateTimeString(),
            'pickup_location' => 'Aéroport Mohammed V',
            'return_location' => 'Aéroport Mohammed V',
            'daily_rate'      => 500.00,
            'payment_method'  => 'cash',
        ];

        $response = $this->authAs($user)->postJson('/api/v1/reservations', $data);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);
    }

    public function test_agent_can_create_reservation(): void
    {
        $user = $this->createAgent();

        $data = [
            'agency_id'       => $this->agency->id,
            'vehicle_id'      => $this->vehicle->id,
            'client_id'       => $this->client->id,
            'pickup_date'     => now()->addDays(2)->toDateTimeString(),
            'return_date'     => now()->addDays(7)->toDateTimeString(),
            'pickup_location' => 'Gare Casa Voyageurs',
            'daily_rate'      => 400.00,
            'payment_method'  => 'card',
        ];

        $response = $this->authAs($user)->postJson('/api/v1/reservations', $data);

        $response->assertStatus(201);
    }

    public function test_viewer_cannot_create_reservation(): void
    {
        $user = $this->createViewer();

        $data = [
            'agency_id'   => $this->agency->id,
            'vehicle_id'  => $this->vehicle->id,
            'client_id'   => $this->client->id,
            'pickup_date' => now()->addDays(1)->toDateTimeString(),
            'return_date' => now()->addDays(3)->toDateTimeString(),
            'daily_rate'  => 300.00,
        ];

        $response = $this->authAs($user)->postJson('/api/v1/reservations', $data);

        $response->assertStatus(403);
    }

    public function test_create_reservation_fails_without_required_fields(): void
    {
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->postJson('/api/v1/reservations', []);

        $response->assertStatus(422);
    }

    // ─── SHOW ─────────────────────────────────────────────────────────

    public function test_authenticated_user_can_view_reservation(): void
    {
        $reservation = Reservation::factory()->create([
            'agency_id' => $this->agency->id, 'vehicle_id' => $this->vehicle->id,
            'client_id' => $this->client->id,
        ]);
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->getJson("/api/v1/reservations/{$reservation->id}");

        $response->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_view_nonexistent_reservation_returns_404(): void
    {
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->getJson('/api/v1/reservations/nonexistent-uuid');

        $response->assertStatus(404);
    }

    // ─── UPDATE ───────────────────────────────────────────────────────

    public function test_user_with_permission_can_update_reservation(): void
    {
        $reservation = Reservation::factory()->create([
            'agency_id' => $this->agency->id, 'vehicle_id' => $this->vehicle->id,
            'client_id' => $this->client->id, 'status' => 'pending',
        ]);
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->putJson("/api/v1/reservations/{$reservation->id}", [
            'pickup_location' => 'Nouveau lieu de prise en charge',
            'notes'           => 'Client VIP',
        ]);

        $response->assertOk();
    }

    // ─── DELETE ───────────────────────────────────────────────────────

    public function test_user_with_permission_can_delete_reservation(): void
    {
        $reservation = Reservation::factory()->create([
            'agency_id' => $this->agency->id, 'vehicle_id' => $this->vehicle->id,
            'client_id' => $this->client->id,
        ]);
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->deleteJson("/api/v1/reservations/{$reservation->id}");

        $response->assertOk();
        $this->assertSoftDeleted('reservations', ['id' => $reservation->id]);
    }

    // ─── STATUS TRANSITIONS ───────────────────────────────────────────

    public function test_can_confirm_pending_reservation(): void
    {
        $reservation = Reservation::factory()->create([
            'agency_id' => $this->agency->id, 'vehicle_id' => $this->vehicle->id,
            'client_id' => $this->client->id, 'status' => 'pending',
        ]);
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->patchJson("/api/v1/reservations/{$reservation->id}/confirm");

        $response->assertOk();
    }

    public function test_can_activate_confirmed_reservation(): void
    {
        $reservation = Reservation::factory()->create([
            'agency_id' => $this->agency->id, 'vehicle_id' => $this->vehicle->id,
            'client_id' => $this->client->id, 'status' => 'confirmed',
        ]);
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->patchJson("/api/v1/reservations/{$reservation->id}/activate");

        $response->assertOk();
    }

    public function test_can_complete_active_reservation(): void
    {
        $reservation = Reservation::factory()->create([
            'agency_id' => $this->agency->id, 'vehicle_id' => $this->vehicle->id,
            'client_id' => $this->client->id, 'status' => 'active',
        ]);
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->patchJson("/api/v1/reservations/{$reservation->id}/complete");

        $response->assertOk();
    }

    public function test_can_cancel_reservation(): void
    {
        $reservation = Reservation::factory()->create([
            'agency_id' => $this->agency->id, 'vehicle_id' => $this->vehicle->id,
            'client_id' => $this->client->id, 'status' => 'pending',
        ]);
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->patchJson("/api/v1/reservations/{$reservation->id}/cancel", [
            'cancellation_reason' => 'Client a annulé',
        ]);

        $response->assertOk();
    }

    // ─── SPECIAL ENDPOINTS ────────────────────────────────────────────

    public function test_can_get_calendar(): void
    {
        Reservation::factory()->count(3)->create([
            'agency_id' => $this->agency->id, 'vehicle_id' => $this->vehicle->id,
            'client_id' => $this->client->id,
        ]);
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->getJson('/api/v1/reservations/calendar');

        $response->assertOk();
    }

    public function test_can_get_overdue_reservations(): void
    {
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->getJson('/api/v1/reservations/overdue');

        $response->assertOk();
    }

    public function test_can_get_statistics(): void
    {
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->getJson('/api/v1/reservations/statistics');

        $response->assertOk();
    }

    // ─── INVOICE ──────────────────────────────────────────────────────

    public function test_can_generate_invoice(): void
    {
        $reservation = Reservation::factory()->create([
            'agency_id' => $this->agency->id, 'vehicle_id' => $this->vehicle->id,
            'client_id' => $this->client->id, 'status' => 'completed',
        ]);
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->getJson("/api/v1/reservations/{$reservation->id}/invoice");

        $response->assertOk();
    }

    // ─── RESTORE ──────────────────────────────────────────────────────

    public function test_can_restore_deleted_reservation(): void
    {
        $reservation = Reservation::factory()->create([
            'agency_id' => $this->agency->id, 'vehicle_id' => $this->vehicle->id,
            'client_id' => $this->client->id,
        ]);
        $reservation->delete();
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->postJson("/api/v1/reservations/{$reservation->id}/restore");

        $response->assertOk();
        $this->assertDatabaseHas('reservations', [
            'id'         => $reservation->id,
            'deleted_at' => null,
        ]);
    }
}

