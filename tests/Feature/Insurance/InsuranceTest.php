<?php

namespace Tests\Feature\Insurance;

use App\Models\Agency;
use App\Models\Insurance;
use App\Models\Vehicle;
use Tests\TestCase;

class InsuranceTest extends TestCase
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

    public function test_authenticated_user_can_list_insurances(): void
    {
        Insurance::factory()->count(5)->create(['vehicle_id' => $this->vehicle->id]);
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->getJson('/api/v1/insurances');

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'success', 'message', 'data', 'meta' => ['current_page', 'total', 'per_page'],
            ]);
    }

    public function test_unauthenticated_user_cannot_list_insurances(): void
    {
        $response = $this->getJson('/api/v1/insurances');

        $response->assertStatus(401);
    }

    public function test_list_insurances_with_pagination(): void
    {
        Insurance::factory()->count(20)->create(['vehicle_id' => $this->vehicle->id]);
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->getJson('/api/v1/insurances?per_page=5');

        $response->assertOk()
            ->assertJsonPath('meta.per_page', 5);
    }

    // ─── STORE ────────────────────────────────────────────────────────

    public function test_authenticated_user_can_create_insurance(): void
    {
        $user = $this->createSuperAdmin();

        $data = [
            'vehicle_id'        => $this->vehicle->id,
            'insurance_company' => 'Wafa Assurance',
            'policy_number'     => 'POL-1234-ABCD-01',
            'type'              => 'comprehensive',
            'start_date'        => now()->toDateString(),
            'end_date'          => now()->addYear()->toDateString(),
            'premium_amount'    => 5000.00,
            'deductible_amount' => 1000.00,
            'is_active'         => true,
            'agent_name'        => 'Mohamed Agent',
            'agent_phone'       => '+212 600 111 222',
        ];

        $response = $this->authAs($user)->postJson('/api/v1/insurances', $data);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('insurances', ['policy_number' => 'POL-1234-ABCD-01']);
    }

    public function test_create_insurance_fails_without_required_fields(): void
    {
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->postJson('/api/v1/insurances', []);

        $response->assertStatus(422);
    }

    public function test_create_insurance_fails_with_invalid_vehicle(): void
    {
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->postJson('/api/v1/insurances', [
            'vehicle_id'        => 'invalid-uuid',
            'insurance_company' => 'Test',
            'policy_number'     => 'POL-0000',
            'type'              => 'comprehensive',
            'start_date'        => now()->toDateString(),
            'end_date'          => now()->addYear()->toDateString(),
            'premium_amount'    => 3000.00,
        ]);

        $response->assertStatus(422);
    }

    public function test_create_insurance_fails_with_end_date_before_start(): void
    {
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->postJson('/api/v1/insurances', [
            'vehicle_id'        => $this->vehicle->id,
            'insurance_company' => 'Test',
            'policy_number'     => 'POL-0001',
            'type'              => 'third_party',
            'start_date'        => now()->toDateString(),
            'end_date'          => now()->subMonth()->toDateString(),
            'premium_amount'    => 2000.00,
        ]);

        $response->assertStatus(422);
    }

    // ─── SHOW ─────────────────────────────────────────────────────────

    public function test_authenticated_user_can_view_insurance(): void
    {
        $insurance = Insurance::factory()->create(['vehicle_id' => $this->vehicle->id]);
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->getJson("/api/v1/insurances/{$insurance->id}");

        $response->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_view_nonexistent_insurance_returns_404(): void
    {
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->getJson('/api/v1/insurances/nonexistent-uuid');

        $response->assertStatus(404);
    }

    // ─── UPDATE ───────────────────────────────────────────────────────

    public function test_authenticated_user_can_update_insurance(): void
    {
        $insurance = Insurance::factory()->create(['vehicle_id' => $this->vehicle->id]);
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->putJson("/api/v1/insurances/{$insurance->id}", [
            'insurance_company' => 'AXA Maroc',
            'premium_amount'    => 8000.00,
        ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('insurances', [
            'id'                => $insurance->id,
            'insurance_company' => 'AXA Maroc',
        ]);
    }

    // ─── DELETE ───────────────────────────────────────────────────────

    public function test_authenticated_user_can_delete_insurance(): void
    {
        $insurance = Insurance::factory()->create(['vehicle_id' => $this->vehicle->id]);
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->deleteJson("/api/v1/insurances/{$insurance->id}");

        $response->assertOk();
        $this->assertSoftDeleted('insurances', ['id' => $insurance->id]);
    }

    // ─── EXPIRED ──────────────────────────────────────────────────────

    public function test_can_list_expired_insurances(): void
    {
        Insurance::factory()->create([
            'vehicle_id' => $this->vehicle->id,
            'end_date'   => now()->subMonth(),
        ]);
        Insurance::factory()->create([
            'vehicle_id' => $this->vehicle->id,
            'end_date'   => now()->addYear(),
        ]);
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->getJson('/api/v1/insurances/expired');

        $response->assertOk();
    }

    // ─── EXPIRING SOON ───────────────────────────────────────────────

    public function test_can_list_expiring_soon_insurances(): void
    {
        Insurance::factory()->create([
            'vehicle_id' => $this->vehicle->id,
            'end_date'   => now()->addDays(15),
        ]);
        Insurance::factory()->create([
            'vehicle_id' => $this->vehicle->id,
            'end_date'   => now()->addYear(),
        ]);
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->getJson('/api/v1/insurances/expiring-soon');

        $response->assertOk();
    }

    // ─── MEDIA ────────────────────────────────────────────────────────

    public function test_can_get_insurance_media(): void
    {
        $insurance = Insurance::factory()->create(['vehicle_id' => $this->vehicle->id]);
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->getJson("/api/v1/insurances/{$insurance->id}/media");

        $response->assertOk();
    }
}

