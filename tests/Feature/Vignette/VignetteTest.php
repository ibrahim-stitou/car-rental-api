<?php

namespace Tests\Feature\Vignette;

use App\Models\Agency;
use App\Models\Vehicle;
use App\Models\Vignette;
use Tests\TestCase;

class VignetteTest extends TestCase
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

    public function test_authenticated_user_can_list_vignettes(): void
    {
        Vignette::factory()->count(5)->create(['vehicle_id' => $this->vehicle->id]);
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->getJson('/api/v1/vignettes');

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'success', 'message', 'data', 'meta' => ['current_page', 'total', 'per_page'],
            ]);
    }

    public function test_unauthenticated_user_cannot_list_vignettes(): void
    {
        $response = $this->getJson('/api/v1/vignettes');

        $response->assertStatus(401);
    }

    public function test_list_vignettes_with_pagination(): void
    {
        Vignette::factory()->count(20)->create(['vehicle_id' => $this->vehicle->id]);
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->getJson('/api/v1/vignettes?per_page=5');

        $response->assertOk()
            ->assertJsonPath('meta.per_page', 5);
    }

    // ─── STORE ────────────────────────────────────────────────────────

    public function test_authenticated_user_can_create_vignette(): void
    {
        $user = $this->createSuperAdmin();

        $data = [
            'vehicle_id'        => $this->vehicle->id,
            'year'              => 2026,
            'issue_date'        => '2026-01-01',
            'expiry_date'       => '2026-12-31',
            'amount'            => 2000.00,
            'payment_method'    => 'bank_transfer',
            'payment_reference' => 'VIG-2026-REF001',
            'is_paid'           => true,
        ];

        $response = $this->authAs($user)->postJson('/api/v1/vignettes', $data);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('vignettes', [
            'vehicle_id' => $this->vehicle->id,
            'year'       => 2026,
        ]);
    }

    public function test_create_vignette_fails_without_required_fields(): void
    {
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->postJson('/api/v1/vignettes', []);

        $response->assertStatus(422);
    }

    public function test_create_vignette_fails_with_invalid_vehicle(): void
    {
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->postJson('/api/v1/vignettes', [
            'vehicle_id'  => 'invalid-uuid',
            'year'        => 2026,
            'issue_date'  => '2026-01-01',
            'expiry_date' => '2026-12-31',
            'amount'      => 1500.00,
        ]);

        $response->assertStatus(422);
    }

    // ─── SHOW ─────────────────────────────────────────────────────────

    public function test_authenticated_user_can_view_vignette(): void
    {
        $vignette = Vignette::factory()->create(['vehicle_id' => $this->vehicle->id]);
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->getJson("/api/v1/vignettes/{$vignette->id}");

        $response->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_view_nonexistent_vignette_returns_404(): void
    {
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->getJson('/api/v1/vignettes/nonexistent-uuid');

        $response->assertStatus(404);
    }

    // ─── UPDATE ───────────────────────────────────────────────────────

    public function test_authenticated_user_can_update_vignette(): void
    {
        $vignette = Vignette::factory()->create(['vehicle_id' => $this->vehicle->id]);
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->putJson("/api/v1/vignettes/{$vignette->id}", [
            'amount'         => 2500.00,
            'payment_method' => 'online',
        ]);

        $response->assertOk()
            ->assertJson(['success' => true]);
    }

    // ─── DELETE ───────────────────────────────────────────────────────

    public function test_authenticated_user_can_delete_vignette(): void
    {
        $vignette = Vignette::factory()->create(['vehicle_id' => $this->vehicle->id]);
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->deleteJson("/api/v1/vignettes/{$vignette->id}");

        $response->assertOk();
        $this->assertSoftDeleted('vignettes', ['id' => $vignette->id]);
    }

    // ─── MARK AS PAID ─────────────────────────────────────────────────

    public function test_can_mark_vignette_as_paid(): void
    {
        $vignette = Vignette::factory()->create([
            'vehicle_id' => $this->vehicle->id,
            'is_paid'    => false,
        ]);
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->patchJson("/api/v1/vignettes/{$vignette->id}/mark-paid", [
            'payment_method'    => 'cash',
            'payment_reference' => 'REF-PAID-001',
        ]);

        $response->assertOk();
    }

    // ─── EXPIRED ──────────────────────────────────────────────────────

    public function test_can_list_expired_vignettes(): void
    {
        Vignette::factory()->create([
            'vehicle_id'  => $this->vehicle->id,
            'expiry_date' => now()->subMonth(),
        ]);
        Vignette::factory()->create([
            'vehicle_id'  => $this->vehicle->id,
            'expiry_date' => now()->addYear(),
        ]);
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->getJson('/api/v1/vignettes/expired');

        $response->assertOk();
    }

    // ─── UNPAID ───────────────────────────────────────────────────────

    public function test_can_list_unpaid_vignettes(): void
    {
        Vignette::factory()->create([
            'vehicle_id' => $this->vehicle->id,
            'is_paid'    => false,
        ]);
        Vignette::factory()->create([
            'vehicle_id' => $this->vehicle->id,
            'is_paid'    => true,
        ]);
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->getJson('/api/v1/vignettes/unpaid');

        $response->assertOk();
    }
}

