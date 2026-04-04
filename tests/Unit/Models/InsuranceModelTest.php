<?php

namespace Tests\Unit\Models;

use App\Models\Agency;
use App\Models\Insurance;
use App\Models\Vehicle;
use Tests\TestCase;

class InsuranceModelTest extends TestCase
{
    // ─── SCOPES ───────────────────────────────────────────────────────

    public function test_scope_active_returns_active_non_expired_insurances(): void
    {
        $agency  = Agency::factory()->create();
        $vehicle = Vehicle::factory()->create(['agency_id' => $agency->id]);

        // Active et non expirée
        Insurance::factory()->create([
            'vehicle_id' => $vehicle->id,
            'is_active'  => true,
            'end_date'   => now()->addYear(),
        ]);

        // Inactive
        Insurance::factory()->create([
            'vehicle_id' => $vehicle->id,
            'is_active'  => false,
            'end_date'   => now()->addYear(),
        ]);

        // Expirée
        Insurance::factory()->create([
            'vehicle_id' => $vehicle->id,
            'is_active'  => true,
            'end_date'   => now()->subDay(),
        ]);

        $active = Insurance::active()->get();
        $this->assertEquals(1, $active->count());
    }

    public function test_scope_expired_returns_past_insurances(): void
    {
        $agency  = Agency::factory()->create();
        $vehicle = Vehicle::factory()->create(['agency_id' => $agency->id]);

        Insurance::factory()->create(['vehicle_id' => $vehicle->id, 'end_date' => now()->subMonth()]);
        Insurance::factory()->create(['vehicle_id' => $vehicle->id, 'end_date' => now()->addYear()]);

        $expired = Insurance::expired()->get();
        $this->assertEquals(1, $expired->count());
    }

    public function test_scope_expiring_soon_returns_insurances_within_30_days(): void
    {
        $agency  = Agency::factory()->create();
        $vehicle = Vehicle::factory()->create(['agency_id' => $agency->id]);

        Insurance::factory()->create(['vehicle_id' => $vehicle->id, 'end_date' => now()->addDays(15)]);
        Insurance::factory()->create(['vehicle_id' => $vehicle->id, 'end_date' => now()->addDays(45)]);
        Insurance::factory()->create(['vehicle_id' => $vehicle->id, 'end_date' => now()->subDay()]);

        $expiringSoon = Insurance::expiringSoon()->get();
        $this->assertEquals(1, $expiringSoon->count());
    }

    public function test_scope_expiring_soon_respects_custom_days(): void
    {
        $agency  = Agency::factory()->create();
        $vehicle = Vehicle::factory()->create(['agency_id' => $agency->id]);

        Insurance::factory()->create(['vehicle_id' => $vehicle->id, 'end_date' => now()->addDays(5)]);
        Insurance::factory()->create(['vehicle_id' => $vehicle->id, 'end_date' => now()->addDays(15)]);

        $expiringSoon7  = Insurance::expiringSoon(7)->get();
        $expiringSoon30 = Insurance::expiringSoon(30)->get();

        $this->assertEquals(1, $expiringSoon7->count());
        $this->assertEquals(2, $expiringSoon30->count());
    }

    // ─── UUID ─────────────────────────────────────────────────────────

    public function test_insurance_id_is_uuid(): void
    {
        $agency    = Agency::factory()->create();
        $vehicle   = Vehicle::factory()->create(['agency_id' => $agency->id]);
        $insurance = Insurance::factory()->create(['vehicle_id' => $vehicle->id]);

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $insurance->id
        );
    }

    // ─── SOFT DELETE ──────────────────────────────────────────────────

    public function test_insurance_is_soft_deleted(): void
    {
        $agency    = Agency::factory()->create();
        $vehicle   = Vehicle::factory()->create(['agency_id' => $agency->id]);
        $insurance = Insurance::factory()->create(['vehicle_id' => $vehicle->id]);

        $insurance->delete();

        $this->assertSoftDeleted('insurances', ['id' => $insurance->id]);
        $this->assertNull(Insurance::find($insurance->id));
    }

    // ─── RELATIONS ────────────────────────────────────────────────────

    public function test_insurance_belongs_to_vehicle(): void
    {
        $agency    = Agency::factory()->create();
        $vehicle   = Vehicle::factory()->create(['agency_id' => $agency->id]);
        $insurance = Insurance::factory()->create(['vehicle_id' => $vehicle->id]);

        $this->assertInstanceOf(Vehicle::class, $insurance->vehicle);
        $this->assertEquals($vehicle->id, $insurance->vehicle->id);
    }

    // ─── CASTS ────────────────────────────────────────────────────────

    public function test_insurance_casts_coverage_details_as_array(): void
    {
        $agency    = Agency::factory()->create();
        $vehicle   = Vehicle::factory()->create(['agency_id' => $agency->id]);
        $insurance = Insurance::factory()->create([
            'vehicle_id'       => $vehicle->id,
            'coverage_details' => ['windshield' => true, 'theft' => false],
        ]);

        $this->assertIsArray($insurance->fresh()->coverage_details);
        $this->assertTrue($insurance->fresh()->coverage_details['windshield']);
    }

    public function test_insurance_casts_is_active_as_boolean(): void
    {
        $agency    = Agency::factory()->create();
        $vehicle   = Vehicle::factory()->create(['agency_id' => $agency->id]);
        $insurance = Insurance::factory()->create([
            'vehicle_id' => $vehicle->id,
            'is_active'  => true,
        ]);

        $this->assertIsBool($insurance->fresh()->is_active);
    }
}

