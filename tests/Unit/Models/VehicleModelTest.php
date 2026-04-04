<?php

namespace Tests\Unit\Models;

use App\Models\Agency;
use App\Models\Vehicle;
use Tests\TestCase;

class VehicleModelTest extends TestCase
{
    // ─── ACCESSORS ────────────────────────────────────────────────────

    public function test_full_name_accessor_returns_brand_model_year(): void
    {
        $vehicle = Vehicle::factory()->make([
            'brand' => 'Toyota',
            'model' => 'Corolla',
            'year'  => 2024,
        ]);

        $this->assertEquals('Toyota Corolla 2024', $vehicle->full_name);
    }

    public function test_is_available_returns_true_when_status_is_available(): void
    {
        $vehicle = Vehicle::factory()->make(['status' => 'available']);

        $this->assertTrue($vehicle->is_available);
    }

    public function test_is_available_returns_false_when_status_is_rented(): void
    {
        $vehicle = Vehicle::factory()->make(['status' => 'rented']);

        $this->assertFalse($vehicle->is_available);
    }

    public function test_is_available_returns_false_when_status_is_maintenance(): void
    {
        $vehicle = Vehicle::factory()->make(['status' => 'maintenance']);

        $this->assertFalse($vehicle->is_available);
    }

    // ─── UUID ─────────────────────────────────────────────────────────

    public function test_vehicle_id_is_uuid(): void
    {
        $agency  = Agency::factory()->create();
        $vehicle = Vehicle::factory()->create(['agency_id' => $agency->id]);

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $vehicle->id
        );
    }

    public function test_vehicle_key_is_not_incrementing(): void
    {
        $vehicle = new Vehicle();
        $this->assertFalse($vehicle->getIncrementing());
        $this->assertEquals('string', $vehicle->getKeyType());
    }

    // ─── SOFT DELETE ──────────────────────────────────────────────────

    public function test_vehicle_is_soft_deleted(): void
    {
        $agency  = Agency::factory()->create();
        $vehicle = Vehicle::factory()->create(['agency_id' => $agency->id]);

        $vehicle->delete();

        $this->assertSoftDeleted('vehicles', ['id' => $vehicle->id]);
        $this->assertNull(Vehicle::find($vehicle->id));
        $this->assertNotNull(Vehicle::withTrashed()->find($vehicle->id));
    }

    public function test_vehicle_can_be_restored(): void
    {
        $agency  = Agency::factory()->create();
        $vehicle = Vehicle::factory()->create(['agency_id' => $agency->id]);
        $vehicle->delete();

        $vehicle->restore();

        $this->assertNotNull(Vehicle::find($vehicle->id));
        $this->assertDatabaseHas('vehicles', ['id' => $vehicle->id, 'deleted_at' => null]);
    }

    // ─── RELATIONS ────────────────────────────────────────────────────

    public function test_vehicle_belongs_to_agency(): void
    {
        $agency  = Agency::factory()->create();
        $vehicle = Vehicle::factory()->create(['agency_id' => $agency->id]);

        $this->assertInstanceOf(Agency::class, $vehicle->agency);
        $this->assertEquals($agency->id, $vehicle->agency->id);
    }

    public function test_vehicle_has_many_reservations(): void
    {
        $vehicle = new Vehicle();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $vehicle->reservations());
    }

    public function test_vehicle_has_many_insurances(): void
    {
        $vehicle = new Vehicle();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $vehicle->insurances());
    }

    public function test_vehicle_has_many_maintenances(): void
    {
        $vehicle = new Vehicle();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $vehicle->maintenances());
    }

    public function test_vehicle_has_many_technical_inspections(): void
    {
        $vehicle = new Vehicle();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $vehicle->technicalInspections());
    }

    public function test_vehicle_has_many_vignettes(): void
    {
        $vehicle = new Vehicle();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $vehicle->vignettes());
    }

    // ─── CASTS ────────────────────────────────────────────────────────

    public function test_vehicle_casts_numeric_fields(): void
    {
        $agency  = Agency::factory()->create();
        $vehicle = Vehicle::factory()->create([
            'agency_id'      => $agency->id,
            'year'           => 2024,
            'seats'          => 5,
            'daily_rate'     => 500.50,
            'deposit_amount' => 3000.00,
            'mileage'        => 15000,
            'is_active'      => true,
        ]);

        $fresh = Vehicle::find($vehicle->id);
        $this->assertIsInt($fresh->year);
        $this->assertIsInt($fresh->seats);
        $this->assertIsInt($fresh->mileage);
        $this->assertIsBool($fresh->is_active);
    }
}

