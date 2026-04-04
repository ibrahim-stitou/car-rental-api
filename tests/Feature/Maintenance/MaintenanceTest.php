<?php

namespace Tests\Feature\Maintenance;

use App\Models\Agency;
use App\Models\Maintenance;
use App\Models\Vehicle;
use Tests\TestCase;

class MaintenanceTest extends TestCase
{
    private Agency $agency;
    private Vehicle $vehicle;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agency  = Agency::factory()->create();
        $this->vehicle = Vehicle::factory()->create(['agency_id' => $this->agency->id]);
    }

    // ─── INDEX ────────────────────────────────────────────────────────

    public function test_authenticated_user_can_list_maintenances(): void
    {
        Maintenance::factory()->count(5)->create(['vehicle_id' => $this->vehicle->id]);
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->getJson('/api/v1/maintenances');

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'success', 'message', 'data', 'meta' => ['current_page', 'total', 'per_page'],
            ]);
    }

    public function test_unauthenticated_user_cannot_list_maintenances(): void
    {
        $response = $this->getJson('/api/v1/maintenances');

        $response->assertStatus(401);
    }

    public function test_list_maintenances_with_pagination(): void
    {
        Maintenance::factory()->count(20)->create(['vehicle_id' => $this->vehicle->id]);
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->getJson('/api/v1/maintenances?per_page=5');

        $response->assertOk()
            ->assertJsonPath('meta.per_page', 5);
    }

    // ─── STORE ────────────────────────────────────────────────────────

    public function test_authenticated_user_can_create_maintenance(): void
    {
        $user = $this->createSuperAdmin();

        $data = [
            'vehicle_id'         => $this->vehicle->id,
            'type'               => 'oil_change',
            'description'        => 'Vidange moteur et remplacement filtre à huile',
            'maintenance_date'   => now()->addDays(3)->toDateString(),
            'mileage_at_service' => 50000,
            'cost'               => 800.00,
            'service_provider'   => 'Garage Atlas',
            'status'             => 'scheduled',
            'priority'           => 'medium',
        ];

        $response = $this->authAs($user)->postJson('/api/v1/maintenances', $data);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('maintenances', [
            'vehicle_id' => $this->vehicle->id,
            'type'       => 'oil_change',
        ]);
    }

    public function test_create_maintenance_fails_without_required_fields(): void
    {
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->postJson('/api/v1/maintenances', []);

        $response->assertStatus(422);
    }

    public function test_create_maintenance_fails_with_invalid_vehicle(): void
    {
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->postJson('/api/v1/maintenances', [
            'vehicle_id'       => 'invalid-uuid',
            'type'             => 'oil_change',
            'description'      => 'Test',
            'maintenance_date' => now()->toDateString(),
            'cost'             => 500.00,
        ]);

        $response->assertStatus(422);
    }

    // ─── SHOW ─────────────────────────────────────────────────────────

    public function test_authenticated_user_can_view_maintenance(): void
    {
        $maintenance = Maintenance::factory()->create(['vehicle_id' => $this->vehicle->id]);
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->getJson("/api/v1/maintenances/{$maintenance->id}");

        $response->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_view_nonexistent_maintenance_returns_404(): void
    {
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->getJson('/api/v1/maintenances/nonexistent-uuid');

        $response->assertStatus(404);
    }

    // ─── UPDATE ───────────────────────────────────────────────────────

    public function test_authenticated_user_can_update_maintenance(): void
    {
        $maintenance = Maintenance::factory()->create([
            'vehicle_id' => $this->vehicle->id,
            'status'     => 'scheduled',
        ]);
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->putJson("/api/v1/maintenances/{$maintenance->id}", [
            'description' => 'Description mise à jour',
            'cost'        => 1200.00,
            'priority'    => 'high',
        ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('maintenances', [
            'id'          => $maintenance->id,
            'description' => 'Description mise à jour',
        ]);
    }

    // ─── DELETE ───────────────────────────────────────────────────────

    public function test_authenticated_user_can_delete_maintenance(): void
    {
        $maintenance = Maintenance::factory()->create(['vehicle_id' => $this->vehicle->id]);
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->deleteJson("/api/v1/maintenances/{$maintenance->id}");

        $response->assertOk();
        $this->assertSoftDeleted('maintenances', ['id' => $maintenance->id]);
    }

    // ─── COMPLETE ─────────────────────────────────────────────────────

    public function test_can_complete_maintenance(): void
    {
        $maintenance = Maintenance::factory()->create([
            'vehicle_id' => $this->vehicle->id,
            'status'     => 'in_progress',
        ]);
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->patchJson("/api/v1/maintenances/{$maintenance->id}/complete");

        $response->assertOk();
    }

    // ─── CANCEL ───────────────────────────────────────────────────────

    public function test_can_cancel_maintenance(): void
    {
        $maintenance = Maintenance::factory()->create([
            'vehicle_id' => $this->vehicle->id,
            'status'     => 'scheduled',
        ]);
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->patchJson("/api/v1/maintenances/{$maintenance->id}/cancel");

        $response->assertOk();
    }

    // ─── SCHEDULED ────────────────────────────────────────────────────

    public function test_can_list_scheduled_maintenances(): void
    {
        Maintenance::factory()->create([
            'vehicle_id' => $this->vehicle->id,
            'status'     => 'scheduled',
        ]);
        Maintenance::factory()->create([
            'vehicle_id' => $this->vehicle->id,
            'status'     => 'completed',
        ]);
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->getJson('/api/v1/maintenances/scheduled');

        $response->assertOk();
    }

    // ─── OVERDUE ──────────────────────────────────────────────────────

    public function test_can_list_overdue_maintenances(): void
    {
        Maintenance::factory()->create([
            'vehicle_id'       => $this->vehicle->id,
            'status'           => 'scheduled',
            'maintenance_date' => now()->subWeek(),
        ]);
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->getJson('/api/v1/maintenances/overdue');

        $response->assertOk();
    }
}

