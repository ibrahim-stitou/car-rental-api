<?php

namespace Tests\Feature\TechnicalInspection;

use App\Models\Agency;
use App\Models\TechnicalInspection;
use App\Models\Vehicle;
use Tests\TestCase;

class TechnicalInspectionTest extends TestCase
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

    public function test_authenticated_user_can_list_technical_inspections(): void
    {
        TechnicalInspection::factory()->count(5)->create(['vehicle_id' => $this->vehicle->id]);
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->getJson('/api/v1/technical-inspections');

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'success', 'message', 'data', 'meta' => ['current_page', 'total', 'per_page'],
            ]);
    }

    public function test_unauthenticated_user_cannot_list_technical_inspections(): void
    {
        $response = $this->getJson('/api/v1/technical-inspections');

        $response->assertStatus(401);
    }

    public function test_list_technical_inspections_with_pagination(): void
    {
        TechnicalInspection::factory()->count(20)->create(['vehicle_id' => $this->vehicle->id]);
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->getJson('/api/v1/technical-inspections?per_page=5');

        $response->assertOk()
            ->assertJsonPath('meta.per_page', 5);
    }

    // ─── STORE ────────────────────────────────────────────────────────

    public function test_authenticated_user_can_create_technical_inspection(): void
    {
        $user = $this->createSuperAdmin();

        $data = [
            'vehicle_id'           => $this->vehicle->id,
            'inspection_date'      => now()->toDateString(),
            'expiry_date'          => now()->addYear()->toDateString(),
            'result'               => 'passed',
            'inspection_center'    => 'Centre Narsa Casablanca',
            'inspector_name'       => 'Hassan Alami',
            'observations'         => 'RAS',
            'cost'                 => 350.00,
            'next_inspection_date' => now()->addYear()->toDateString(),
        ];

        $response = $this->authAs($user)->postJson('/api/v1/technical-inspections', $data);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('technical_inspections', [
            'vehicle_id'        => $this->vehicle->id,
            'inspection_center' => 'Centre Narsa Casablanca',
        ]);
    }

    public function test_create_technical_inspection_fails_without_required_fields(): void
    {
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->postJson('/api/v1/technical-inspections', []);

        $response->assertStatus(422);
    }

    public function test_create_technical_inspection_fails_with_invalid_vehicle(): void
    {
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->postJson('/api/v1/technical-inspections', [
            'vehicle_id'      => 'invalid-uuid',
            'inspection_date' => now()->toDateString(),
            'expiry_date'     => now()->addYear()->toDateString(),
            'result'          => 'passed',
        ]);

        $response->assertStatus(422);
    }

    public function test_create_technical_inspection_with_failed_result(): void
    {
        $user = $this->createSuperAdmin();

        $data = [
            'vehicle_id'        => $this->vehicle->id,
            'inspection_date'   => now()->toDateString(),
            'expiry_date'       => now()->addMonths(3)->toDateString(),
            'result'            => 'failed',
            'inspection_center' => 'Centre Narsa Rabat',
            'observations'      => 'Freins avant usés, pneus à changer',
            'cost'              => 350.00,
        ];

        $response = $this->authAs($user)->postJson('/api/v1/technical-inspections', $data);

        $response->assertStatus(201);
    }

    // ─── SHOW ─────────────────────────────────────────────────────────

    public function test_authenticated_user_can_view_technical_inspection(): void
    {
        $inspection = TechnicalInspection::factory()->create(['vehicle_id' => $this->vehicle->id]);
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->getJson("/api/v1/technical-inspections/{$inspection->id}");

        $response->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_view_nonexistent_technical_inspection_returns_404(): void
    {
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->getJson('/api/v1/technical-inspections/nonexistent-uuid');

        $response->assertStatus(404);
    }

    // ─── UPDATE ───────────────────────────────────────────────────────

    public function test_authenticated_user_can_update_technical_inspection(): void
    {
        $inspection = TechnicalInspection::factory()->create(['vehicle_id' => $this->vehicle->id]);
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->putJson("/api/v1/technical-inspections/{$inspection->id}", [
            'result'       => 'passed',
            'observations' => 'Observations mises à jour',
            'cost'         => 500.00,
        ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('technical_inspections', [
            'id'           => $inspection->id,
            'observations' => 'Observations mises à jour',
        ]);
    }

    // ─── DELETE ───────────────────────────────────────────────────────

    public function test_authenticated_user_can_delete_technical_inspection(): void
    {
        $inspection = TechnicalInspection::factory()->create(['vehicle_id' => $this->vehicle->id]);
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->deleteJson("/api/v1/technical-inspections/{$inspection->id}");

        $response->assertOk();
        $this->assertSoftDeleted('technical_inspections', ['id' => $inspection->id]);
    }

    // ─── EXPIRED ──────────────────────────────────────────────────────

    public function test_can_list_expired_technical_inspections(): void
    {
        TechnicalInspection::factory()->create([
            'vehicle_id'  => $this->vehicle->id,
            'expiry_date' => now()->subMonth(),
        ]);
        TechnicalInspection::factory()->create([
            'vehicle_id'  => $this->vehicle->id,
            'expiry_date' => now()->addYear(),
        ]);
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->getJson('/api/v1/technical-inspections/expired');

        $response->assertOk();
    }

    // ─── EXPIRING SOON ───────────────────────────────────────────────

    public function test_can_list_expiring_soon_technical_inspections(): void
    {
        TechnicalInspection::factory()->create([
            'vehicle_id'  => $this->vehicle->id,
            'expiry_date' => now()->addDays(15),
        ]);
        TechnicalInspection::factory()->create([
            'vehicle_id'  => $this->vehicle->id,
            'expiry_date' => now()->addYear(),
        ]);
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->getJson('/api/v1/technical-inspections/expiring-soon');

        $response->assertOk();
    }
}

