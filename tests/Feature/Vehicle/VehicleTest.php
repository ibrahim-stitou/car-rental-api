<?php

namespace Tests\Feature\Vehicle;

use App\Models\Agency;
use App\Models\Vehicle;
use Tests\TestCase;

class VehicleTest extends TestCase
{
    private Agency $agency;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agency = Agency::factory()->create();
    }

    // ─── INDEX ────────────────────────────────────────────────────────

    public function test_authenticated_user_can_list_vehicles(): void
    {
        Vehicle::factory()->count(5)->create(['agency_id' => $this->agency->id]);
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->getJson('/api/v1/vehicles');

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'success', 'message', 'data', 'meta' => ['current_page', 'total', 'per_page'],
            ]);
    }

    public function test_unauthenticated_user_cannot_list_vehicles(): void
    {
        $response = $this->getJson('/api/v1/vehicles');

        $response->assertStatus(401);
    }

    public function test_list_vehicles_with_pagination(): void
    {
        Vehicle::factory()->count(20)->create(['agency_id' => $this->agency->id]);
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->getJson('/api/v1/vehicles?per_page=5');

        $response->assertOk()
            ->assertJsonPath('meta.per_page', 5);
    }

    public function test_list_vehicles_filter_by_status(): void
    {
        Vehicle::factory()->create(['agency_id' => $this->agency->id, 'status' => 'available']);
        Vehicle::factory()->create(['agency_id' => $this->agency->id, 'status' => 'rented']);
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->getJson('/api/v1/vehicles?status=available');

        $response->assertOk();
    }

    public function test_list_vehicles_filter_by_agency(): void
    {
        $agency2 = Agency::factory()->create();
        Vehicle::factory()->count(3)->create(['agency_id' => $this->agency->id]);
        Vehicle::factory()->count(2)->create(['agency_id' => $agency2->id]);
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->getJson("/api/v1/vehicles?agency_id={$this->agency->id}");

        $response->assertOk();
    }

    public function test_list_vehicles_filter_by_category(): void
    {
        Vehicle::factory()->create(['agency_id' => $this->agency->id, 'category' => 'suv']);
        Vehicle::factory()->create(['agency_id' => $this->agency->id, 'category' => 'economy']);
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->getJson('/api/v1/vehicles?category=suv');

        $response->assertOk();
    }

    public function test_list_vehicles_with_search(): void
    {
        Vehicle::factory()->create(['agency_id' => $this->agency->id, 'brand' => 'Toyota', 'model' => 'Corolla']);
        Vehicle::factory()->create(['agency_id' => $this->agency->id, 'brand' => 'Renault', 'model' => 'Clio']);
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->getJson('/api/v1/vehicles?search=Toyota');

        $response->assertOk();
    }

    // ─── STORE ────────────────────────────────────────────────────────

    public function test_user_with_permission_can_create_vehicle(): void
    {
        $user = $this->createSuperAdmin();

        $data = [
            'agency_id'           => $this->agency->id,
            'brand'               => 'Toyota',
            'model'               => 'Corolla',
            'year'                => 2024,
            'registration_number' => 'AB-123-CD',
            'color'               => 'Blanc',
            'category'            => 'compact',
            'fuel_type'           => 'gasoline',
            'transmission'        => 'automatic',
            'seats'               => 5,
            'daily_rate'          => 350.00,
            'deposit_amount'      => 2000.00,
            'mileage'             => 15000,
        ];

        $response = $this->authAs($user)->postJson('/api/v1/vehicles', $data);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('vehicles', ['registration_number' => 'AB-123-CD']);
    }

    public function test_viewer_cannot_create_vehicle(): void
    {
        $user = $this->createViewer();

        $data = [
            'agency_id'           => $this->agency->id,
            'brand'               => 'Toyota',
            'model'               => 'Yaris',
            'year'                => 2024,
            'registration_number' => 'EF-456-GH',
            'category'            => 'economy',
            'fuel_type'           => 'gasoline',
            'transmission'        => 'manual',
            'seats'               => 5,
            'daily_rate'          => 250.00,
        ];

        $response = $this->authAs($user)->postJson('/api/v1/vehicles', $data);

        $response->assertStatus(403);
    }

    public function test_create_vehicle_fails_without_required_fields(): void
    {
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->postJson('/api/v1/vehicles', []);

        $response->assertStatus(422);
    }

    public function test_create_vehicle_fails_with_invalid_agency(): void
    {
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->postJson('/api/v1/vehicles', [
            'agency_id' => 'invalid-uuid',
            'brand'     => 'Toyota',
            'model'     => 'Test',
            'year'      => 2024,
        ]);

        $response->assertStatus(422);
    }

    // ─── SHOW ─────────────────────────────────────────────────────────

    public function test_authenticated_user_can_view_vehicle(): void
    {
        $vehicle = Vehicle::factory()->create(['agency_id' => $this->agency->id]);
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->getJson("/api/v1/vehicles/{$vehicle->id}");

        $response->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_view_nonexistent_vehicle_returns_404(): void
    {
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->getJson('/api/v1/vehicles/nonexistent-uuid');

        $response->assertStatus(404);
    }

    // ─── UPDATE ───────────────────────────────────────────────────────

    public function test_user_with_permission_can_update_vehicle(): void
    {
        $vehicle = Vehicle::factory()->create(['agency_id' => $this->agency->id]);
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->putJson("/api/v1/vehicles/{$vehicle->id}", [
            'brand' => 'Peugeot',
            'model' => '308',
            'color' => 'Noir',
        ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('vehicles', [
            'id'    => $vehicle->id,
            'brand' => 'Peugeot',
        ]);
    }

    public function test_viewer_cannot_update_vehicle(): void
    {
        $vehicle = Vehicle::factory()->create(['agency_id' => $this->agency->id]);
        $user = $this->createViewer();

        $response = $this->authAs($user)->putJson("/api/v1/vehicles/{$vehicle->id}", [
            'color' => 'Rouge',
        ]);

        $response->assertStatus(403);
    }

    // ─── DELETE ───────────────────────────────────────────────────────

    public function test_user_with_permission_can_delete_vehicle(): void
    {
        $vehicle = Vehicle::factory()->create(['agency_id' => $this->agency->id]);
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->deleteJson("/api/v1/vehicles/{$vehicle->id}");

        $response->assertOk();
        $this->assertSoftDeleted('vehicles', ['id' => $vehicle->id]);
    }

    public function test_viewer_cannot_delete_vehicle(): void
    {
        $vehicle = Vehicle::factory()->create(['agency_id' => $this->agency->id]);
        $user = $this->createViewer();

        $response = $this->authAs($user)->deleteJson("/api/v1/vehicles/{$vehicle->id}");

        $response->assertStatus(403);
    }

    // ─── STATUS UPDATE ────────────────────────────────────────────────

    public function test_can_update_vehicle_status(): void
    {
        $vehicle = Vehicle::factory()->create(['agency_id' => $this->agency->id, 'status' => 'available']);
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->patchJson("/api/v1/vehicles/{$vehicle->id}/status", [
            'status' => 'maintenance',
        ]);

        $response->assertOk();
    }

    // ─── HISTORY ──────────────────────────────────────────────────────

    public function test_can_get_vehicle_history(): void
    {
        $vehicle = Vehicle::factory()->create(['agency_id' => $this->agency->id]);
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->getJson("/api/v1/vehicles/{$vehicle->id}/history");

        $response->assertOk();
    }

    // ─── RESERVATIONS ─────────────────────────────────────────────────

    public function test_can_get_vehicle_reservations(): void
    {
        $vehicle = Vehicle::factory()->create(['agency_id' => $this->agency->id]);
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->getJson("/api/v1/vehicles/{$vehicle->id}/reservations");

        $response->assertOk();
    }

    // ─── RESTORE ──────────────────────────────────────────────────────

    public function test_can_restore_deleted_vehicle(): void
    {
        $vehicle = Vehicle::factory()->create(['agency_id' => $this->agency->id]);
        $vehicle->delete();
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->postJson("/api/v1/vehicles/{$vehicle->id}/restore");

        $response->assertOk();
        $this->assertDatabaseHas('vehicles', [
            'id'         => $vehicle->id,
            'deleted_at' => null,
        ]);
    }
}

