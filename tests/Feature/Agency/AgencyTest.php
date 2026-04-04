<?php

namespace Tests\Feature\Agency;

use App\Models\Agency;
use App\Models\User;
use App\Models\Vehicle;
use Tests\TestCase;

class AgencyTest extends TestCase
{
    // ─── INDEX ────────────────────────────────────────────────────────

    public function test_super_admin_can_list_agencies(): void
    {
        Agency::factory()->count(5)->create();
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->getJson('/api/v1/agencies');

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'success', 'message', 'data', 'meta' => ['current_page', 'total', 'per_page'],
            ]);
    }

    public function test_admin_can_list_agencies(): void
    {
        Agency::factory()->count(3)->create();
        $user = $this->createAdmin();

        $response = $this->authAs($user)->getJson('/api/v1/agencies');

        $response->assertOk();
    }

    public function test_agent_cannot_list_agencies(): void
    {
        $user = $this->createAgent();

        $response = $this->authAs($user)->getJson('/api/v1/agencies');

        $response->assertStatus(403);
    }

    public function test_viewer_cannot_list_agencies(): void
    {
        $user = $this->createViewer();

        $response = $this->authAs($user)->getJson('/api/v1/agencies');

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_list_agencies(): void
    {
        $response = $this->getJson('/api/v1/agencies');

        $response->assertStatus(401);
    }

    public function test_list_agencies_with_pagination(): void
    {
        Agency::factory()->count(20)->create();
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->getJson('/api/v1/agencies?per_page=5');

        $response->assertOk()
            ->assertJsonPath('meta.per_page', 5);
    }

    public function test_list_agencies_with_search(): void
    {
        Agency::factory()->create(['name' => 'GES Cars Casablanca']);
        Agency::factory()->create(['name' => 'GES Cars Marrakech']);
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->getJson('/api/v1/agencies?search=Casablanca');

        $response->assertOk();
    }

    public function test_list_agencies_filter_by_city(): void
    {
        Agency::factory()->create(['city' => 'Casablanca']);
        Agency::factory()->create(['city' => 'Rabat']);
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->getJson('/api/v1/agencies?city=Casablanca');

        $response->assertOk();
    }

    // ─── STORE ────────────────────────────────────────────────────────

    public function test_super_admin_can_create_agency(): void
    {
        $user = $this->createSuperAdmin();

        $data = [
            'name'    => 'Nouvelle Agence',
            'email'   => 'nouvelle@ges-cars.ma',
            'address' => '10 Rue de Test',
            'city'    => 'Casablanca',
            'country' => 'MA',
            'phone'   => '+212 522 111 222',
        ];

        $response = $this->authAs($user)->postJson('/api/v1/agencies', $data);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('agencies', ['email' => 'nouvelle@ges-cars.ma']);
    }

    public function test_admin_can_create_agency(): void
    {
        $user = $this->createAdmin();

        $data = [
            'name'  => 'Agence Admin',
            'email' => 'admin-agence@ges-cars.ma',
        ];

        $response = $this->authAs($user)->postJson('/api/v1/agencies', $data);

        $response->assertStatus(201);
    }

    public function test_create_agency_fails_without_name(): void
    {
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->postJson('/api/v1/agencies', [
            'email' => 'test@ges-cars.ma',
        ]);

        $response->assertStatus(422);
    }

    public function test_create_agency_fails_without_email(): void
    {
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->postJson('/api/v1/agencies', [
            'name' => 'Test Agency',
        ]);

        $response->assertStatus(422);
    }

    public function test_create_agency_fails_with_duplicate_email(): void
    {
        $user = $this->createSuperAdmin();
        Agency::factory()->create(['email' => 'duplicate@ges-cars.ma']);

        $response = $this->authAs($user)->postJson('/api/v1/agencies', [
            'name'  => 'Autre Agence',
            'email' => 'duplicate@ges-cars.ma',
        ]);

        $response->assertStatus(422);
    }

    public function test_create_agency_fails_with_invalid_email(): void
    {
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->postJson('/api/v1/agencies', [
            'name'  => 'Test',
            'email' => 'invalid-email',
        ]);

        $response->assertStatus(422);
    }

    public function test_create_agency_with_manager(): void
    {
        $user = $this->createSuperAdmin();
        $manager = User::factory()->create();

        $response = $this->authAs($user)->postJson('/api/v1/agencies', [
            'name'       => 'Agence avec Manager',
            'email'      => 'manager-test@ges-cars.ma',
            'manager_id' => $manager->id,
        ]);

        $response->assertStatus(201);
    }

    // ─── SHOW ─────────────────────────────────────────────────────────

    public function test_super_admin_can_view_agency(): void
    {
        $agency = Agency::factory()->create();
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->getJson("/api/v1/agencies/{$agency->id}");

        $response->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_view_nonexistent_agency_returns_404(): void
    {
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->getJson('/api/v1/agencies/nonexistent-uuid');

        $response->assertStatus(404);
    }

    // ─── UPDATE ───────────────────────────────────────────────────────

    public function test_super_admin_can_update_agency(): void
    {
        $agency = Agency::factory()->create();
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->putJson("/api/v1/agencies/{$agency->id}", [
            'name' => 'Nom Mis à Jour',
        ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('agencies', [
            'id'   => $agency->id,
            'name' => 'Nom Mis à Jour',
        ]);
    }

    public function test_update_agency_with_duplicate_email_fails(): void
    {
        $user = $this->createSuperAdmin();
        $agency1 = Agency::factory()->create(['email' => 'first@ges.ma']);
        $agency2 = Agency::factory()->create(['email' => 'second@ges.ma']);

        $response = $this->authAs($user)->putJson("/api/v1/agencies/{$agency2->id}", [
            'email' => 'first@ges.ma',
        ]);

        $response->assertStatus(422);
    }

    // ─── DELETE ───────────────────────────────────────────────────────

    public function test_super_admin_can_delete_agency(): void
    {
        $agency = Agency::factory()->create();
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->deleteJson("/api/v1/agencies/{$agency->id}");

        $response->assertOk();
        $this->assertSoftDeleted('agencies', ['id' => $agency->id]);
    }

    public function test_delete_nonexistent_agency_returns_404(): void
    {
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->deleteJson('/api/v1/agencies/nonexistent-uuid');

        $response->assertStatus(404);
    }

    // ─── RESTORE ──────────────────────────────────────────────────────

    public function test_super_admin_can_restore_deleted_agency(): void
    {
        $agency = Agency::factory()->create();
        $agency->delete();
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->postJson("/api/v1/agencies/{$agency->id}/restore");

        $response->assertOk();
        $this->assertDatabaseHas('agencies', [
            'id'         => $agency->id,
            'deleted_at' => null,
        ]);
    }

    // ─── VEHICLES ─────────────────────────────────────────────────────

    public function test_super_admin_can_list_agency_vehicles(): void
    {
        $agency = Agency::factory()->create();
        Vehicle::factory()->count(3)->create(['agency_id' => $agency->id]);
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->getJson("/api/v1/agencies/{$agency->id}/vehicles");

        $response->assertOk()
            ->assertJson(['success' => true]);
    }
}

