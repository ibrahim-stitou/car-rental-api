<?php

namespace App\Modules\Website\Services;

use App\Models\Client;
use App\Models\Reservation;
use App\Models\Vehicle;
use App\Models\WebReservation;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class WebsiteService
{
    // ─── Public (no auth) ───────────────────────────────────────────────────

    public function publicVehicles(array $filters = [], int $perPage = 12): LengthAwarePaginator
    {
        return Vehicle::query()
            ->where('show_on_website', true)
            ->where('is_active', true)
            ->where('status', 'available')
            ->when($filters['category'] ?? null, fn($q, $v) => $q->where('category', $v))
            ->when($filters['min_price'] ?? null, fn($q, $v) => $q->where(
                DB::raw('COALESCE(website_price_override, daily_rate)'), '>=', $v
            ))
            ->when($filters['max_price'] ?? null, fn($q, $v) => $q->where(
                DB::raw('COALESCE(website_price_override, daily_rate)'), '<=', $v
            ))
            ->when($filters['seats'] ?? null, fn($q, $v) => $q->where('seats', '>=', $v))
            ->with('agency:id,name,city')
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function publicVehicle(string $id): Vehicle
    {
        return Vehicle::where('show_on_website', true)
            ->where('is_active', true)
            ->with('agency:id,name,city,phone,address')
            ->findOrFail($id);
    }

    public function submitReservation(array $data): WebReservation
    {
        return WebReservation::create([
            'reference'       => WebReservation::generateReference(),
            'vehicle_id'      => $data['vehicle_id'],
            'first_name'      => $data['first_name'],
            'last_name'       => $data['last_name'],
            'email'           => $data['email'],
            'phone'           => $data['phone'],
            'pickup_date'     => $data['pickup_date'],
            'return_date'     => $data['return_date'],
            'pickup_location' => $data['pickup_location'] ?? null,
            'message'         => $data['message'] ?? null,
            'ip_address'      => $data['ip_address'] ?? null,
        ]);
    }

    // ─── Admin ───────────────────────────────────────────────────────────────

    public function listWebReservations(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return WebReservation::with('vehicle')
            ->when($filters['status'] ?? null, fn($q, $v) => $q->where('status', $v))
            ->when($filters['vehicle_id'] ?? null, fn($q, $v) => $q->where('vehicle_id', $v))
            ->latest()
            ->paginate($perPage);
    }

    public function findWebReservation(string $id): WebReservation
    {
        return WebReservation::with(['vehicle', 'reservation'])->findOrFail($id);
    }

    public function approve(string $id, array $extra = []): WebReservation
    {
        return DB::transaction(function () use ($id, $extra) {
            $webRes = WebReservation::findOrFail($id);

            if ($webRes->status !== 'pending') {
                abort(422, 'Cette demande ne peut plus être approuvée.');
            }

            // Find or create client
            $client = Client::firstOrCreate(
                ['email' => $webRes->email],
                [
                    'agency_id'  => $webRes->vehicle->agency_id,
                    'first_name' => $webRes->first_name,
                    'last_name'  => $webRes->last_name,
                    'phone'      => $webRes->phone,
                    'is_active'  => true,
                ]
            );

            $vehicle  = $webRes->vehicle;
            $days     = $webRes->days;
            $price    = $vehicle->website_price_override ?? $vehicle->daily_rate;
            $total    = $price * $days;

            // Create reservation
            $reservation = Reservation::create([
                'agency_id'      => $vehicle->agency_id,
                'vehicle_id'     => $webRes->vehicle_id,
                'client_id'      => $client->id,
                'pickup_date'    => $webRes->pickup_date,
                'return_date'    => $webRes->return_date,
                'pickup_location'=> $webRes->pickup_location ?? '',
                'return_location'=> $webRes->pickup_location ?? '',
                'daily_rate'     => $price,
                'total_days'     => $days,
                'subtotal'       => $total,
                'total_amount'   => $total,
                'status'         => 'confirmed',
                'payment_status' => 'pending',
                'notes'          => 'Réservation web — ' . ($webRes->message ?? ''),
                ...$extra,
            ]);

            $webRes->update([
                'status'         => 'converted',
                'reservation_id' => $reservation->id,
            ]);

            return $webRes->fresh(['vehicle', 'reservation']);
        });
    }

    public function reject(string $id, string $reason): WebReservation
    {
        $webRes = WebReservation::findOrFail($id);

        if ($webRes->status !== 'pending') {
            abort(422, 'Cette demande ne peut plus être rejetée.');
        }

        $webRes->update(['status' => 'rejected', 'rejection_reason' => $reason]);
        return $webRes;
    }

    public function websiteVehicles(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Vehicle::query()
            ->when($filters['agency_id'] ?? null, fn($q, $v) => $q->where('agency_id', $v))
            ->when(isset($filters['show_on_website']), fn($q) => $q->where('show_on_website', $filters['show_on_website']))
            ->with('agency:id,name')
            ->latest()
            ->paginate($perPage);
    }

    public function updateVehicleWebsite(string $id, array $data): Vehicle
    {
        $vehicle = Vehicle::findOrFail($id);
        $vehicle->update($data);
        return $vehicle->fresh();
    }

    public function stats(): array
    {
        return [
            'total_requests'     => WebReservation::count(),
            'pending'            => WebReservation::where('status', 'pending')->count(),
            'approved'           => WebReservation::where('status', 'approved')->count(),
            'rejected'           => WebReservation::where('status', 'rejected')->count(),
            'converted'          => WebReservation::where('status', 'converted')->count(),
            'vehicles_on_site'   => Vehicle::where('show_on_website', true)->where('is_active', true)->count(),
            'today_requests'     => WebReservation::whereDate('created_at', today())->count(),
        ];
    }
}
