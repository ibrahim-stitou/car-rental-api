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
use Illuminate\Support\Facades\Cache;
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
        $cacheKey = 'dashboard_stats_' . ($agencyId ?? 'global');

        $data = Cache::remember($cacheKey, 300, function () use ($agencyId) {
            return [
                'vehicles'            => $this->vehicleStats($agencyId),
                'reservations'        => $this->reservationStats($agencyId),
                'billing'             => $this->billingStats($agencyId),
                'clients'             => $this->clientStats($agencyId),
                'expiring'            => $this->expiringStats($agencyId),
                'monthly_revenue'     => $this->monthlyRevenue($agencyId),
                'recent_reservations' => $this->recentReservations($agencyId),
            ];
        });

        return $this->success($data);
    }

    private function vehicleStats(?string $agencyId): array
    {
        $counts = Vehicle::query()
            ->when($agencyId, fn($q) => $q->where('agency_id', $agencyId))
            ->selectRaw('status, COUNT(*) as cnt')
            ->groupBy('status')
            ->pluck('cnt', 'status');

        return [
            'total'          => $counts->sum(),
            'available'      => (int) ($counts['available'] ?? 0),
            'rented'         => (int) ($counts['rented'] ?? 0),
            'maintenance'    => (int) ($counts['maintenance'] ?? 0),
            'out_of_service' => (int) ($counts['out_of_service'] ?? 0),
        ];
    }

    private function reservationStats(?string $agencyId): array
    {
        $q = Reservation::query()->when($agencyId, fn($q) => $q->where('agency_id', $agencyId));

        $counts = (clone $q)->selectRaw('status, COUNT(*) as cnt')->groupBy('status')->pluck('cnt', 'status');

        return [
            'total'            => $counts->sum(),
            'pending'          => (int) ($counts['pending']   ?? 0),
            'confirmed'        => (int) ($counts['confirmed'] ?? 0),
            'active'           => (int) ($counts['active']    ?? 0),
            'completed'        => (int) ($counts['completed'] ?? 0),
            'cancelled'        => (int) ($counts['cancelled'] ?? 0),
            'overdue'          => (clone $q)->where('status', 'active')->where('return_date', '<', now())->count(),
            'pending_action'   => (int) ($counts['pending'] ?? 0) + (int) ($counts['confirmed'] ?? 0),
            'upcoming_returns' => (clone $q)->where('status', 'active')->whereBetween('return_date', [now(), now()->addDays(7)])->count(),
            'this_month'       => (clone $q)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
        ];
    }

    private function recentReservations(?string $agencyId): mixed
    {
        return Reservation::with(['vehicle:id,brand,model,registration_number', 'client:id,first_name,last_name'])
            ->when($agencyId, fn($q) => $q->where('agency_id', $agencyId))
            ->orderByDesc('created_at')
            ->limit(8)
            ->get(['id', 'reservation_number', 'status', 'pickup_date', 'return_date', 'total_amount', 'vehicle_id', 'client_id']);
    }

    private function billingStats(?string $agencyId): array
    {
        $q = BillingDocument::query()->when($agencyId, fn($q) => $q->where('agency_id', $agencyId));

        $revenueThisMonth = (float) (clone $q)->where('type', 'FA')->where('status', 'paid')
            ->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)
            ->sum('total_amount');

        $revenueLastMonth = (float) (clone $q)->where('type', 'FA')->where('status', 'paid')
            ->whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->sum('total_amount');

        return [
            'total_invoices'      => (clone $q)->where('type', 'FA')->count(),
            'total_revenue'       => (float) (clone $q)->where('type', 'FA')->where('status', 'paid')->sum('total_amount'),
            'revenue_this_month'  => $revenueThisMonth,
            'revenue_last_month'  => $revenueLastMonth,
            'pending_amount'      => (float) (clone $q)->where('type', 'FA')->whereIn('status', ['pending', 'approved'])->sum('total_amount'),
            'draft_count'         => (clone $q)->where('status', 'draft')->count(),
            'paid_count'          => (clone $q)->where('status', 'paid')->count(),
        ];
    }

    private function clientStats(?string $agencyId): array
    {
        $q = Client::query()->when($agencyId, fn($q) => $q->where('agency_id', $agencyId));

        return [
            'total'       => (clone $q)->count(),
            'active'      => (clone $q)->where('is_blacklisted', false)->count(),
            'blacklisted' => (clone $q)->where('is_blacklisted', true)->count(),
            'new_this_month' => (clone $q)->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)->count(),
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

    private function monthlyRevenue(?string $agencyId): array
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
            ->get()
            ->map(fn($row) => ['month' => $row->month, 'revenue' => (float) $row->revenue])
            ->values()
            ->toArray();
    }
}
