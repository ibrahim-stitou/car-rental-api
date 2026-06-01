<?php

namespace App\Http\Controllers\Api;

use App\Core\Http\Controllers\BaseController;
use App\Models\BillingDocument;
use App\Models\Client;
use App\Models\Insurance;
use App\Models\Maintenance;
use App\Models\Reservation;
use App\Models\TechnicalInspection;
use App\Models\Vehicle;
use App\Models\Vignette;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends BaseController
{
    /**
     * @OA\Get(path="/dashboard/statistics", summary="KPI globaux", tags={"Dashboard"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="agency_id", in="query", @OA\Schema(type="string")),
     *   @OA\Response(response=200, description="Success")
     * )
     */
    public function statistics(Request $request): JsonResponse
    {
        $agencyId = $request->query('agency_id');

        $vehicles      = $this->vehicleStats($agencyId);
        $reservations  = $this->reservationStats($agencyId);
        $billing       = $this->billingStats($agencyId);
        $clients       = $this->clientStats($agencyId);
        $expiring      = $this->expiringStats($agencyId);
        $monthlyRevenue = $this->monthlyRevenue($agencyId);

        return $this->success([
            'vehicles'        => $vehicles,
            'reservations'    => $reservations,
            'billing'         => $billing,
            'clients'         => $clients,
            'expiring'        => $expiring,
            'monthly_revenue' => $monthlyRevenue,
        ]);
    }

    private function vehicleStats(?string $agencyId): array
    {
        $q = Vehicle::query()->when($agencyId, fn($q) => $q->where('agency_id', $agencyId));

        return [
            'total'          => (clone $q)->count(),
            'available'      => (clone $q)->where('status', 'available')->count(),
            'rented'         => (clone $q)->where('status', 'rented')->count(),
            'maintenance'    => (clone $q)->where('status', 'maintenance')->count(),
            'out_of_service' => (clone $q)->where('status', 'out_of_service')->count(),
        ];
    }

    private function reservationStats(?string $agencyId): array
    {
        $q = Reservation::query()->when($agencyId, fn($q) => $q->where('agency_id', $agencyId));

        return [
            'total'     => (clone $q)->count(),
            'pending'   => (clone $q)->where('status', 'pending')->count(),
            'confirmed' => (clone $q)->where('status', 'confirmed')->count(),
            'active'    => (clone $q)->where('status', 'active')->count(),
            'completed' => (clone $q)->where('status', 'completed')->count(),
            'cancelled' => (clone $q)->where('status', 'cancelled')->count(),
            'overdue'   => (clone $q)->where('status', 'active')
                ->where('return_date', '<', now())->count(),
        ];
    }

    private function billingStats(?string $agencyId): array
    {
        $q = BillingDocument::query()->when($agencyId, fn($q) => $q->where('agency_id', $agencyId));

        return [
            'total_invoices' => (clone $q)->where('type', 'FA')->count(),
            'total_revenue'  => (float) (clone $q)->where('type', 'FA')->where('status', 'paid')
                ->sum('total_amount'),
            'pending_amount' => (float) (clone $q)->where('type', 'FA')
                ->whereIn('status', ['pending', 'approved'])->sum('total_amount'),
            'draft_count'    => (clone $q)->where('status', 'draft')->count(),
            'paid_count'     => (clone $q)->where('status', 'paid')->count(),
        ];
    }

    private function clientStats(?string $agencyId): array
    {
        $q = Client::query()->when($agencyId, fn($q) => $q->where('agency_id', $agencyId));

        return [
            'total'  => (clone $q)->count(),
            'active' => (clone $q)->where('is_active', true)->count(),
        ];
    }

    private function expiringStats(?string $agencyId): array
    {
        $vehicleScope = fn($q) => $q->when($agencyId, fn($q) => $q->where('agency_id', $agencyId));

        $insurances = Insurance::query()
            ->when($agencyId, fn($q) => $q->whereHas('vehicle', $vehicleScope))
            ->whereBetween('end_date', [now(), now()->addDays(30)])
            ->count();

        $inspections = TechnicalInspection::query()
            ->when($agencyId, fn($q) => $q->whereHas('vehicle', $vehicleScope))
            ->whereBetween('next_inspection_date', [now(), now()->addDays(30)])
            ->count();

        $vignettes = Vignette::query()
            ->when($agencyId, fn($q) => $q->whereHas('vehicle', $vehicleScope))
            ->whereBetween('expiry_date', [now(), now()->addDays(30)])
            ->count();

        $maintenances = Maintenance::query()
            ->when($agencyId, fn($q) => $q->whereHas('vehicle', $vehicleScope))
            ->where('status', 'scheduled')
            ->whereBetween('maintenance_date', [now(), now()->addDays(7)])
            ->count();

        return compact('insurances', 'inspections', 'vignettes', 'maintenances');
    }

    private function monthlyRevenue(?string $agencyId): mixed
    {
        return BillingDocument::select(
            DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"),
            DB::raw('SUM(total_amount) as revenue')
        )
            ->where('type', 'FA')
            ->where('status', 'paid')
            ->when($agencyId, fn($q) => $q->where('agency_id', $agencyId))
            ->where('created_at', '>=', now()->subMonths(12))
            ->groupBy('month')
            ->orderBy('month')
            ->get();
    }
}
