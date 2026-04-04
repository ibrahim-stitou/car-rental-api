<?php

namespace Tests\Unit\Models;

use App\Models\Agency;
use App\Models\Client;
use App\Models\User;
use App\Models\Vehicle;
use Tests\TestCase;

class AgencyModelTest extends TestCase
{
    // ─── UUID ─────────────────────────────────────────────────────────

    public function test_agency_id_is_uuid(): void
    {
        $agency = Agency::factory()->create();

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $agency->id
        );
    }

    public function test_agency_key_is_not_incrementing(): void
    {
        $agency = new Agency();
        $this->assertFalse($agency->getIncrementing());
        $this->assertEquals('string', $agency->getKeyType());
    }

    // ─── SOFT DELETE ──────────────────────────────────────────────────

    public function test_agency_is_soft_deleted(): void
    {
        $agency = Agency::factory()->create();
        $agency->delete();

        $this->assertSoftDeleted('agencies', ['id' => $agency->id]);
        $this->assertNull(Agency::find($agency->id));
        $this->assertNotNull(Agency::withTrashed()->find($agency->id));
    }

    public function test_agency_can_be_restored(): void
    {
        $agency = Agency::factory()->create();
        $agency->delete();
        $agency->restore();

        $this->assertNotNull(Agency::find($agency->id));
        $this->assertDatabaseHas('agencies', ['id' => $agency->id, 'deleted_at' => null]);
    }

    // ─── CASTS ────────────────────────────────────────────────────────

    public function test_agency_is_active_is_cast_to_boolean(): void
    {
        $agency = Agency::factory()->create(['is_active' => true]);
        $this->assertIsBool($agency->fresh()->is_active);
    }

    // ─── RELATIONS ────────────────────────────────────────────────────

    public function test_agency_has_many_vehicles(): void
    {
        $agency = Agency::factory()->create();
        Vehicle::factory()->count(3)->create(['agency_id' => $agency->id]);

        $this->assertEquals(3, $agency->vehicles()->count());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $agency->vehicles());
    }

    public function test_agency_has_many_clients(): void
    {
        $agency = Agency::factory()->create();
        Client::factory()->count(5)->create(['agency_id' => $agency->id]);

        $this->assertEquals(5, $agency->clients()->count());
    }

    public function test_agency_has_many_users(): void
    {
        $agency = Agency::factory()->create();
        User::factory()->count(2)->create(['agency_id' => $agency->id]);

        $this->assertEquals(2, $agency->users()->count());
    }

    public function test_agency_belongs_to_manager(): void
    {
        $manager = User::factory()->create();
        $agency  = Agency::factory()->create(['manager_id' => $manager->id]);

        $this->assertInstanceOf(User::class, $agency->manager);
        $this->assertEquals($manager->id, $agency->manager->id);
    }

    public function test_agency_without_manager_has_null_manager(): void
    {
        $agency = Agency::factory()->create(['manager_id' => null]);
        $this->assertNull($agency->manager);
    }

    // ─── FILLABLE ─────────────────────────────────────────────────────

    public function test_agency_fillable_fields_are_correct(): void
    {
        $agency = new Agency();
        $this->assertContains('name', $agency->getFillable());
        $this->assertContains('email', $agency->getFillable());
        $this->assertContains('address', $agency->getFillable());
        $this->assertContains('city', $agency->getFillable());
        $this->assertContains('phone', $agency->getFillable());
        $this->assertContains('is_active', $agency->getFillable());
    }
}

