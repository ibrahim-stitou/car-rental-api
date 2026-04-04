<?php

namespace Tests\Unit\Models;

use App\Models\Agency;
use App\Models\Maintenance;
use App\Models\TechnicalInspection;
use App\Models\Vehicle;
use App\Models\Vignette;
use Tests\TestCase;

class VignetteMaintenanceInspectionModelTest extends TestCase
{
    private Agency $agency;
    private Vehicle $vehicle;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agency  = Agency::factory()->create();
        $this->vehicle = Vehicle::factory()->create(['agency_id' => $this->agency->id]);
    }

    // ─── VIGNETTE SCOPES ──────────────────────────────────────────────

    public function test_vignette_scope_expired_returns_past_vignettes(): void
    {
        Vignette::factory()->create(['vehicle_id' => $this->vehicle->id, 'expiry_date' => now()->subDay()]);
        Vignette::factory()->create(['vehicle_id' => $this->vehicle->id, 'expiry_date' => now()->addYear()]);

        $this->assertEquals(1, Vignette::expired()->count());
    }

    public function test_vignette_scope_unpaid_returns_unpaid_vignettes(): void
    {
        Vignette::factory()->create(['vehicle_id' => $this->vehicle->id, 'is_paid' => false]);
        Vignette::factory()->create(['vehicle_id' => $this->vehicle->id, 'is_paid' => false]);
        Vignette::factory()->create(['vehicle_id' => $this->vehicle->id, 'is_paid' => true]);

        $this->assertEquals(2, Vignette::unpaid()->count());
    }

    public function test_vignette_scope_current_year_filters_correctly(): void
    {
        Vignette::factory()->create(['vehicle_id' => $this->vehicle->id, 'year' => now()->year]);
        Vignette::factory()->create(['vehicle_id' => $this->vehicle->id, 'year' => now()->year - 1]);

        $this->assertEquals(1, Vignette::currentYear()->count());
    }

    public function test_vignette_soft_delete_works(): void
    {
        $vignette = Vignette::factory()->create(['vehicle_id' => $this->vehicle->id]);
        $vignette->delete();

        $this->assertSoftDeleted('vignettes', ['id' => $vignette->id]);
    }

    public function test_vignette_belongs_to_vehicle(): void
    {
        $vignette = Vignette::factory()->create(['vehicle_id' => $this->vehicle->id]);

        $this->assertInstanceOf(Vehicle::class, $vignette->vehicle);
        $this->assertEquals($this->vehicle->id, $vignette->vehicle->id);
    }

    public function test_vignette_casts_is_paid_as_boolean(): void
    {
        $vignette = Vignette::factory()->create(['vehicle_id' => $this->vehicle->id, 'is_paid' => true]);

        $this->assertIsBool($vignette->fresh()->is_paid);
    }

    public function test_vignette_id_is_uuid(): void
    {
        $vignette = Vignette::factory()->create(['vehicle_id' => $this->vehicle->id]);

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $vignette->id
        );
    }

    // ─── MAINTENANCE SCOPES ───────────────────────────────────────────

    public function test_maintenance_scope_scheduled_filters_correctly(): void
    {
        Maintenance::factory()->create(['vehicle_id' => $this->vehicle->id, 'status' => 'scheduled']);
        Maintenance::factory()->create(['vehicle_id' => $this->vehicle->id, 'status' => 'completed']);
        Maintenance::factory()->create(['vehicle_id' => $this->vehicle->id, 'status' => 'cancelled']);

        $this->assertEquals(1, Maintenance::scheduled()->count());
    }

    public function test_maintenance_scope_overdue_filters_past_scheduled(): void
    {
        // Planifiée mais dépassée
        Maintenance::factory()->create([
            'vehicle_id'       => $this->vehicle->id,
            'status'           => 'scheduled',
            'maintenance_date' => now()->subWeek(),
        ]);
        // Planifiée dans le futur
        Maintenance::factory()->create([
            'vehicle_id'       => $this->vehicle->id,
            'status'           => 'scheduled',
            'maintenance_date' => now()->addWeek(),
        ]);
        // Complétée dépassée (ne doit pas être retournée)
        Maintenance::factory()->create([
            'vehicle_id'       => $this->vehicle->id,
            'status'           => 'completed',
            'maintenance_date' => now()->subWeek(),
        ]);

        $this->assertEquals(1, Maintenance::overdue()->count());
    }

    public function test_maintenance_scope_by_vehicle_filters_correctly(): void
    {
        $vehicle2 = Vehicle::factory()->create(['agency_id' => $this->agency->id]);

        Maintenance::factory()->count(3)->create(['vehicle_id' => $this->vehicle->id, 'status' => 'scheduled']);
        Maintenance::factory()->count(2)->create(['vehicle_id' => $vehicle2->id, 'status' => 'scheduled']);

        $this->assertEquals(3, Maintenance::byVehicle($this->vehicle->id)->count());
        $this->assertEquals(2, Maintenance::byVehicle($vehicle2->id)->count());
    }

    public function test_maintenance_soft_delete_works(): void
    {
        $maintenance = Maintenance::factory()->create(['vehicle_id' => $this->vehicle->id]);
        $maintenance->delete();

        $this->assertSoftDeleted('maintenances', ['id' => $maintenance->id]);
    }

    public function test_maintenance_belongs_to_vehicle(): void
    {
        $maintenance = Maintenance::factory()->create(['vehicle_id' => $this->vehicle->id]);

        $this->assertInstanceOf(Vehicle::class, $maintenance->vehicle);
        $this->assertEquals($this->vehicle->id, $maintenance->vehicle->id);
    }

    public function test_maintenance_id_is_uuid(): void
    {
        $maintenance = Maintenance::factory()->create(['vehicle_id' => $this->vehicle->id]);

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $maintenance->id
        );
    }

    // ─── TECHNICAL INSPECTION SCOPES ─────────────────────────────────

    public function test_inspection_scope_expired_returns_past_inspections(): void
    {
        TechnicalInspection::factory()->create(['vehicle_id' => $this->vehicle->id, 'expiry_date' => now()->subDay()]);
        TechnicalInspection::factory()->create(['vehicle_id' => $this->vehicle->id, 'expiry_date' => now()->addYear()]);

        $this->assertEquals(1, TechnicalInspection::expired()->count());
    }

    public function test_inspection_scope_expiring_soon_returns_within_30_days(): void
    {
        TechnicalInspection::factory()->create(['vehicle_id' => $this->vehicle->id, 'expiry_date' => now()->addDays(10)]);
        TechnicalInspection::factory()->create(['vehicle_id' => $this->vehicle->id, 'expiry_date' => now()->addDays(60)]);

        $this->assertEquals(1, TechnicalInspection::expiringSoon()->count());
    }

    public function test_inspection_scope_by_vehicle_filters_correctly(): void
    {
        $vehicle2 = Vehicle::factory()->create(['agency_id' => $this->agency->id]);

        TechnicalInspection::factory()->count(2)->create(['vehicle_id' => $this->vehicle->id]);
        TechnicalInspection::factory()->count(4)->create(['vehicle_id' => $vehicle2->id]);

        $this->assertEquals(2, TechnicalInspection::byVehicle($this->vehicle->id)->count());
        $this->assertEquals(4, TechnicalInspection::byVehicle($vehicle2->id)->count());
    }

    public function test_inspection_soft_delete_works(): void
    {
        $inspection = TechnicalInspection::factory()->create(['vehicle_id' => $this->vehicle->id]);
        $inspection->delete();

        $this->assertSoftDeleted('technical_inspections', ['id' => $inspection->id]);
    }

    public function test_inspection_belongs_to_vehicle(): void
    {
        $inspection = TechnicalInspection::factory()->create(['vehicle_id' => $this->vehicle->id]);

        $this->assertInstanceOf(Vehicle::class, $inspection->vehicle);
        $this->assertEquals($this->vehicle->id, $inspection->vehicle->id);
    }

    public function test_inspection_id_is_uuid(): void
    {
        $inspection = TechnicalInspection::factory()->create(['vehicle_id' => $this->vehicle->id]);

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $inspection->id
        );
    }
}

