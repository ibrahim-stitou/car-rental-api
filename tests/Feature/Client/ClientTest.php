<?php

namespace Tests\Feature\Client;

use App\Models\Agency;
use App\Models\Client;
use App\Models\Reservation;
use App\Models\Vehicle;
use Tests\TestCase;

class ClientTest extends TestCase
{
    private Agency $agency;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agency = Agency::factory()->create();
    }

    // ─── INDEX ────────────────────────────────────────────────────────

    public function test_authenticated_user_can_list_clients(): void
    {
        Client::factory()->count(5)->create(['agency_id' => $this->agency->id]);
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->getJson('/api/v1/clients');

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'success', 'message', 'data', 'meta' => ['current_page', 'total', 'per_page'],
            ]);
    }

    public function test_unauthenticated_user_cannot_list_clients(): void
    {
        $response = $this->getJson('/api/v1/clients');

        $response->assertStatus(401);
    }

    public function test_list_clients_with_pagination(): void
    {
        Client::factory()->count(20)->create(['agency_id' => $this->agency->id]);
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->getJson('/api/v1/clients?per_page=5');

        $response->assertOk()
            ->assertJsonPath('meta.per_page', 5);
    }

    public function test_list_clients_with_search(): void
    {
        Client::factory()->create(['agency_id' => $this->agency->id, 'first_name' => 'Ahmed', 'last_name' => 'Benali']);
        Client::factory()->create(['agency_id' => $this->agency->id, 'first_name' => 'Sara', 'last_name' => 'Idrissi']);
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->getJson('/api/v1/clients?search=Ahmed');

        $response->assertOk();
    }

    public function test_list_clients_filter_by_agency(): void
    {
        $agency2 = Agency::factory()->create();
        Client::factory()->count(3)->create(['agency_id' => $this->agency->id]);
        Client::factory()->count(2)->create(['agency_id' => $agency2->id]);
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->getJson("/api/v1/clients?agency_id={$this->agency->id}");

        $response->assertOk();
    }

    // ─── STORE ────────────────────────────────────────────────────────

    public function test_user_with_permission_can_create_client(): void
    {
        $user = $this->createSuperAdmin();

        $data = [
            'agency_ids'               => [$this->agency->id],
            'first_name'               => 'Ahmed',
            'last_name'                => 'Benali',
            'email'                    => 'ahmed.benali@example.com',
            'phone'                    => '+212 600 111 222',
            'date_of_birth'            => '1990-05-15',
            'nationality'              => 'MA',
            'id_type'                  => 'cin',
            'id_number'                => 'AB123456',
            'id_expiry_date'           => '2030-12-31',
            'driving_license_number'   => '12/654321',
            'driving_license_category' => 'B',
            'driving_license_expiry'   => '2030-06-30',
            'address'                  => '10 Rue de Test',
            'city'                     => 'Casablanca',
            'country'                  => 'MA',
        ];

        $response = $this->authAs($user)->postJson('/api/v1/clients', $data);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('clients', ['email' => 'ahmed.benali@example.com']);
    }

    public function test_agent_can_create_client(): void
    {
        $user = $this->createAgent();

        $data = [
            'agency_ids' => [$this->agency->id],
            'first_name' => 'Sara',
            'last_name'  => 'Idrissi',
            'email'      => 'sara@example.com',
            'phone'      => '+212 600 333 444',
        ];

        $response = $this->authAs($user)->postJson('/api/v1/clients', $data);

        $response->assertStatus(201);
    }

    public function test_viewer_cannot_create_client(): void
    {
        $user = $this->createViewer();

        $response = $this->authAs($user)->postJson('/api/v1/clients', [
            'agency_ids' => [$this->agency->id],
            'first_name' => 'Test',
            'last_name'  => 'User',
            'email'      => 'test@example.com',
            'phone'      => '+212 600 000 000',
        ]);

        $response->assertStatus(403);
    }

    public function test_create_client_fails_without_required_fields(): void
    {
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->postJson('/api/v1/clients', []);

        $response->assertStatus(422);
    }

    public function test_create_client_fails_with_invalid_email(): void
    {
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->postJson('/api/v1/clients', [
            'agency_ids' => [$this->agency->id],
            'first_name' => 'Test',
            'last_name'  => 'User',
            'email'      => 'invalid-email',
            'phone'      => '+212 600 000 000',
        ]);

        $response->assertStatus(422);
    }

    // ─── PERSONNE MORALE ──────────────────────────────────────────────

    public function test_user_with_permission_can_create_moral_client(): void
    {
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->postJson('/api/v1/clients', [
            'agency_ids'         => [$this->agency->id],
            'client_type'        => 'moral',
            'company_name'       => 'Acme SARL',
            'company_type'       => 'SARL',
            'company_ice'        => '001234567000089',
            'company_address'    => '12 Boulevard Zérktouni',
            'company_city'       => 'Casablanca',
            'company_country'    => 'MA',
            'email'              => 'contact@acme.example',
            'phone'              => '+212 600 555 666',
            'bank_name'          => 'Attijariwafa Bank',
            'bank_account_name'  => 'Acme SARL',
            'bank_account_number' => 'RIBA00000001',
            'bank_address'       => 'Casablanca',
        ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('clients', [
            'client_type'         => 'moral',
            'company_name'        => 'Acme SARL',
            'company_ice'         => '001234567000089',
            'bank_account_number' => 'RIBA00000001',
            'first_name'          => null,
            'last_name'           => null,
        ]);
    }

    public function test_create_moral_client_fails_without_company_name(): void
    {
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->postJson('/api/v1/clients', [
            'agency_ids'  => [$this->agency->id],
            'client_type' => 'moral',
            'email'       => 'contact@acme2.example',
            'phone'       => '+212 600 777 888',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['company_name']);
    }

    public function test_moral_client_strips_personal_fields(): void
    {
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->postJson('/api/v1/clients', [
            'agency_ids'            => [$this->agency->id],
            'client_type'           => 'moral',
            'company_name'          => 'Acme SARL',
            'company_address'       => '12 Boulevard Zérktouni',
            'email'                 => 'contact@acme3.example',
            'phone'                 => '+212 600 999 000',
            'first_name'            => 'NeDoitPasExister',
            'last_name'             => 'Nope',
            'id_number'             => 'AB999999',
            'driving_license_number' => '12/999999',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('clients', [
            'company_name'           => 'Acme SARL',
            'first_name'             => null,
            'last_name'              => null,
            'id_number'              => null,
            'driving_license_number' => null,
        ]);
    }

    public function test_physical_client_strips_company_fields(): void
    {
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->postJson('/api/v1/clients', [
            'agency_ids'   => [$this->agency->id],
            'first_name'   => 'Ahmed',
            'last_name'    => 'Benali',
            'email'        => 'ahmed2@example.com',
            'phone'        => '+212 600 111 333',
            'company_name' => 'NeDoitPasExister',
            'bank_name'    => 'Nope',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('clients', [
            'company_name' => null,
            'bank_name'    => null,
        ]);
    }

    public function test_list_clients_filter_by_client_type_moral(): void
    {
        Client::factory()->moral()->create();
        Client::factory()->count(2)->create();
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->getJson('/api/v1/clients?client_type=moral');

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id');
        $this->assertCount(1, $ids);
        $this->assertTrue(Client::whereIn('id', $ids)->get()->every(fn($c) => $c->client_type === 'moral'));
    }

    // ─── SHOW ─────────────────────────────────────────────────────────

    public function test_authenticated_user_can_view_client(): void
    {
        $client = Client::factory()->create(['agency_id' => $this->agency->id]);
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->getJson("/api/v1/clients/{$client->id}");

        $response->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_view_nonexistent_client_returns_404(): void
    {
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->getJson('/api/v1/clients/nonexistent-uuid');

        $response->assertStatus(404);
    }

    // ─── UPDATE ───────────────────────────────────────────────────────

    public function test_user_with_permission_can_update_client(): void
    {
        $client = Client::factory()->create(['agency_id' => $this->agency->id]);
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->putJson("/api/v1/clients/{$client->id}", [
            'first_name' => 'NouveauPrenom',
            'last_name'  => 'NouveauNom',
        ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('clients', [
            'id'         => $client->id,
            'first_name' => 'NouveauPrenom',
        ]);
    }

    public function test_user_with_permission_can_update_moral_client(): void
    {
        $client = Client::factory()->moral()->create();
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->putJson("/api/v1/clients/{$client->id}", [
            'client_type'    => 'moral',
            'company_name'   => 'Nouvelle Raison Sociale',
            'company_address' => 'Nouvelle adresse',
            'bank_name'      => 'BMCE Bank',
        ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('clients', [
            'id'           => $client->id,
            'company_name' => 'Nouvelle Raison Sociale',
            'bank_name'    => 'BMCE Bank',
        ]);
    }

    public function test_show_moral_client_returns_full_name_and_company(): void
    {
        $client = Client::factory()->moral()->create();
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->getJson("/api/v1/clients/{$client->id}");

        $response->assertOk()
            ->assertJsonPath('data.client_type', 'moral')
            ->assertJsonPath('data.full_name', $client->company_name)
            ->assertJsonPath('data.first_name', null)
            ->assertJsonPath('data.company_ice', $client->company_ice);
    }

    public function test_viewer_cannot_update_client(): void
    {
        $client = Client::factory()->create(['agency_id' => $this->agency->id]);
        $user = $this->createViewer();

        $response = $this->authAs($user)->putJson("/api/v1/clients/{$client->id}", [
            'first_name' => 'Changed',
        ]);

        $response->assertStatus(403);
    }

    // ─── DELETE ───────────────────────────────────────────────────────

    public function test_user_with_permission_can_delete_client(): void
    {
        $client = Client::factory()->create(['agency_id' => $this->agency->id]);
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->deleteJson("/api/v1/clients/{$client->id}");

        $response->assertOk();
        $this->assertSoftDeleted('clients', ['id' => $client->id]);
    }

    public function test_viewer_cannot_delete_client(): void
    {
        $client = Client::factory()->create(['agency_id' => $this->agency->id]);
        $user = $this->createViewer();

        $response = $this->authAs($user)->deleteJson("/api/v1/clients/{$client->id}");

        $response->assertStatus(403);
    }

    // ─── BLACKLIST ────────────────────────────────────────────────────

    public function test_can_blacklist_client(): void
    {
        $client = Client::factory()->create(['agency_id' => $this->agency->id, 'is_blacklisted' => false]);
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->patchJson("/api/v1/clients/{$client->id}/blacklist", [
            'reason' => 'Comportement inapproprié',
        ]);

        $response->assertOk();
    }

    public function test_can_unblacklist_client(): void
    {
        $client = Client::factory()->blacklisted()->create(['agency_id' => $this->agency->id]);
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->patchJson("/api/v1/clients/{$client->id}/unblacklist");

        $response->assertOk();
    }

    public function test_can_list_blacklisted_clients(): void
    {
        Client::factory()->count(2)->blacklisted()->create(['agency_id' => $this->agency->id]);
        Client::factory()->count(3)->create(['agency_id' => $this->agency->id]);
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->getJson('/api/v1/clients/blacklisted');

        $response->assertOk();
    }

    // ─── RESERVATIONS ─────────────────────────────────────────────────

    public function test_can_get_client_reservations(): void
    {
        $client = Client::factory()->create(['agency_id' => $this->agency->id]);
        $vehicle = Vehicle::factory()->create(['agency_id' => $this->agency->id]);
        Reservation::factory()->count(2)->create([
            'agency_id'  => $this->agency->id,
            'vehicle_id' => $vehicle->id,
            'client_id'  => $client->id,
        ]);
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->getJson("/api/v1/clients/{$client->id}/reservations");

        $response->assertOk();
    }

    // ─── RESTORE ──────────────────────────────────────────────────────

    public function test_can_restore_deleted_client(): void
    {
        $client = Client::factory()->create(['agency_id' => $this->agency->id]);
        $client->delete();
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->postJson("/api/v1/clients/{$client->id}/restore");

        $response->assertOk();
        $this->assertDatabaseHas('clients', [
            'id'         => $client->id,
            'deleted_at' => null,
        ]);
    }
}

