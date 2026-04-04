<?php

namespace Tests\Unit\Models;

use App\Models\Agency;
use App\Models\Client;
use App\Models\User;
use Tests\TestCase;

class ClientModelTest extends TestCase
{
    // ─── ACCESSORS ────────────────────────────────────────────────────

    public function test_full_name_accessor_returns_concatenated_name(): void
    {
        $client = Client::factory()->make([
            'first_name' => 'Ahmed',
            'last_name'  => 'Benali',
        ]);

        $this->assertEquals('Ahmed Benali', $client->full_name);
    }

    public function test_is_license_valid_returns_true_for_future_expiry(): void
    {
        $client = Client::factory()->make([
            'driving_license_expiry' => now()->addYear(),
        ]);

        $this->assertTrue($client->is_license_valid);
    }

    public function test_is_license_valid_returns_false_for_past_expiry(): void
    {
        $client = Client::factory()->make([
            'driving_license_expiry' => now()->subDay(),
        ]);

        $this->assertFalse($client->is_license_valid);
    }

    public function test_is_license_valid_returns_false_when_null(): void
    {
        $client = Client::factory()->make([
            'driving_license_expiry' => null,
        ]);

        $this->assertFalse($client->is_license_valid);
    }

    // ─── SCOPES ───────────────────────────────────────────────────────

    public function test_scope_blacklisted_returns_only_blacklisted(): void
    {
        $agency = Agency::factory()->create();

        Client::factory()->count(3)->blacklisted()->create(['agency_id' => $agency->id]);
        Client::factory()->count(2)->create(['agency_id' => $agency->id, 'is_blacklisted' => false]);

        $blacklisted = Client::blacklisted()->get();

        $this->assertEquals(3, $blacklisted->count());
        $blacklisted->each(fn($c) => $this->assertTrue($c->is_blacklisted));
    }

    // ─── SOFT DELETE ──────────────────────────────────────────────────

    public function test_client_is_soft_deleted(): void
    {
        $agency = Agency::factory()->create();
        $client = Client::factory()->create(['agency_id' => $agency->id]);

        $client->delete();

        $this->assertSoftDeleted('clients', ['id' => $client->id]);
        $this->assertNull(Client::find($client->id));
        $this->assertNotNull(Client::withTrashed()->find($client->id));
    }

    public function test_client_can_be_restored(): void
    {
        $agency = Agency::factory()->create();
        $client = Client::factory()->create(['agency_id' => $agency->id]);
        $client->delete();

        $client->restore();

        $this->assertNotNull(Client::find($client->id));
        $this->assertDatabaseHas('clients', ['id' => $client->id, 'deleted_at' => null]);
    }

    // ─── UUID ─────────────────────────────────────────────────────────

    public function test_client_id_is_uuid(): void
    {
        $agency = Agency::factory()->create();
        $client = Client::factory()->create(['agency_id' => $agency->id]);

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $client->id
        );
    }

    public function test_client_key_is_not_incrementing(): void
    {
        $client = new Client();
        $this->assertFalse($client->getIncrementing());
        $this->assertEquals('string', $client->getKeyType());
    }

    // ─── RELATIONS ────────────────────────────────────────────────────

    public function test_client_belongs_to_agency(): void
    {
        $agency = Agency::factory()->create();
        $client = Client::factory()->create(['agency_id' => $agency->id]);

        $this->assertInstanceOf(Agency::class, $client->agency);
        $this->assertEquals($agency->id, $client->agency->id);
    }

    public function test_client_has_many_reservations(): void
    {
        $agency = Agency::factory()->create();
        $client = Client::factory()->create(['agency_id' => $agency->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $client->reservations());
    }
}

