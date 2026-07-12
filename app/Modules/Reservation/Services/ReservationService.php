<?php

namespace App\Modules\Reservation\Services;

use App\Models\Reservation;
use App\Models\Vehicle;
use App\Modules\Notification\Services\NotificationService;
use App\Modules\Reservation\Repositories\ReservationRepository;
use App\Services\PdfService;
use Illuminate\Pagination\LengthAwarePaginator;

class ReservationService
{
    public function __construct(
        protected ReservationRepository $repository,
        protected NotificationService $notificationService,
    ) {}

    public function datatable(array $filters = [])
    {
        return $this->repository->datatable($filters, ['agency', 'vehicle', 'client'], function ($dataTable) {
            $dataTable->addColumn('client_name', fn($r) => $r->client?->full_name ?? '—')
                ->addColumn('vehicle_name', fn($r) => $r->vehicle?->full_name ?? '—')
                ->addColumn('agency_name', fn($r) => $r->agency?->name ?? '—')
                ->addColumn('duration', fn($r) => $r->total_days . ' jour(s)')
                ->filterColumn('client_name', function ($query, $keyword) {
                    $query->whereHas('client', function ($q) use ($keyword) {
                        $q->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$keyword}%"]);
                    });
                });
        });
    }

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage, $filters, ['agency', 'vehicle', 'client', 'creator'], 'created_at', 'desc', [], ['payments' => 'amount']);
    }

    public function search(string $term, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->search($term, $this->repository->getSearchFields(), ['vehicle', 'client'], $perPage);
    }

    public function find(string $id): Reservation
    {
        return $this->repository->findByIdOrFail($id, ['agency', 'vehicle', 'client', 'creator'], ['payments' => 'amount']);
    }

    public function create(array $data): Reservation
    {
        $data['created_by'] = auth('api')->id();
        $data['status'] = $data['status'] ?? 'pending';

        $vehicle = Vehicle::findOrFail($data['vehicle_id']);
        $data['daily_rate'] = $data['daily_rate'] ?? $vehicle->daily_rate;
        $data['deposit_amount'] = $data['deposit_amount'] ?? $vehicle->deposit_amount;

        $reservation = new Reservation($data);
        $reservation->calculateTotal();
        $data = array_merge($data, [
            'total_days'      => $reservation->total_days,
            'subtotal'        => $reservation->subtotal,
            'discount_amount' => $reservation->discount_amount,
            'total_amount'    => $reservation->total_amount,
        ]);

        $reservation = $this->repository->create($data);

        $this->notificationService->notifyReservationCreated($reservation);

        return $reservation;
    }

    public function update(string $id, array $data): Reservation
    {
        return $this->repository->update($id, $data);
    }

    public function delete(string $id): bool
    {
        return $this->repository->delete($id);
    }

    public function restore(string $id): Reservation
    {
        return $this->repository->restore($id);
    }

    public function confirm(string $id): Reservation
    {
        $reservation = $this->repository->findByIdOrFail($id);
        $reservation->update(['status' => 'confirmed']);
        $reservation = $reservation->fresh();

        $this->notificationService->notifyReservationConfirmed($reservation);

        return $reservation;
    }

    /**
     * Generate the contract PDF exactly once and lock it. Once
     * contract_generated_at is set, subsequent calls are a no-op — the
     * stored file remains the source of truth even if the reservation
     * is edited afterwards.
     */
    public function generateAndLockContract(Reservation $reservation): Reservation
    {
        if ($reservation->contract_generated_at) {
            return $reservation;
        }

        app(PdfService::class)->saveReservationContractToMedia($reservation);
        $reservation->update(['contract_generated_at' => now()]);

        return $reservation->fresh();
    }

    public function activate(string $id, array $data): Reservation
    {
        $reservation = $this->repository->findByIdOrFail($id);
        $reservation->update(array_merge($data, ['status' => 'active']));
        $reservation->vehicle->update(['status' => 'rented']);
        $reservation = $reservation->fresh();

        $this->notificationService->notifyReservationActivated($reservation);

        return $reservation;
    }

    public function complete(string $id, array $data): Reservation
    {
        $reservation = $this->repository->findByIdOrFail($id);
        $reservation->fill(array_merge($data, [
            'status'             => 'completed',
            'actual_return_date' => $data['actual_return_date'] ?? now(),
        ]));

        // Additional fees can be adjusted at checkout — recalculate the total so
        // balance/payment_status stay accurate for final settlement.
        if (array_key_exists('additional_fees', $data)) {
            $reservation->calculateTotal();
        }

        $reservation->save();
        $reservation->syncPaymentStatus();
        $reservation->vehicle->update(['status' => 'available']);
        $reservation = $reservation->fresh();

        $this->notificationService->notifyReservationCompleted($reservation);

        return $reservation;
    }

    public function cancel(string $id, ?string $reason = null): Reservation
    {
        $reservation = $this->repository->findByIdOrFail($id);
        $reservation->update([
            'status'              => 'cancelled',
            'cancellation_reason' => $reason,
            'cancelled_at'        => now(),
        ]);
        if ($reservation->vehicle->status === 'rented') {
            $reservation->vehicle->update(['status' => 'available']);
        }
        $reservation = $reservation->fresh();

        $this->notificationService->notifyReservationCancelled($reservation);

        return $reservation;
    }

    public function noShow(string $id): Reservation
    {
        $reservation = $this->repository->findByIdOrFail($id);
        $reservation->update(['status' => 'no_show']);
        if ($reservation->vehicle->status === 'rented') {
            $reservation->vehicle->update(['status' => 'available']);
        }
        return $reservation->fresh();
    }

    public function overdue(int $perPage = 15): LengthAwarePaginator
    {
        return Reservation::overdue()->with(['vehicle', 'client'])->paginate($perPage);
    }

    public function calendar(array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        $query = Reservation::with(['vehicle', 'client'])
            ->whereIn('status', ['confirmed', 'active', 'pending']);

        if (!empty($filters['agency_id'])) {
            $query->where('agency_id', $filters['agency_id']);
        }
        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->where(function ($q) use ($filters) {
                $q->whereBetween('pickup_date', [$filters['start_date'], $filters['end_date']])
                  ->orWhereBetween('return_date', [$filters['start_date'], $filters['end_date']]);
            });
        }

        return $query->orderBy('pickup_date')->get();
    }

    public function statistics(array $filters = []): array
    {
        $query = Reservation::query();
        if (!empty($filters['agency_id'])) {
            $query->where('agency_id', $filters['agency_id']);
        }

        return [
            'total'       => $query->count(),
            'pending'     => (clone $query)->where('status', 'pending')->count(),
            'confirmed'   => (clone $query)->where('status', 'confirmed')->count(),
            'active'      => (clone $query)->where('status', 'active')->count(),
            'completed'   => (clone $query)->where('status', 'completed')->count(),
            'cancelled'   => (clone $query)->where('status', 'cancelled')->count(),
            'overdue'     => (clone $query)->where('status', 'active')->where('return_date', '<', now())->count(),
            'total_revenue' => (clone $query)->where('status', 'completed')->sum('total_amount'),
        ];
    }
}

