<?php

namespace App\Modules\Maintenance\Services;

use App\Models\Maintenance;
use App\Modules\Maintenance\Repositories\MaintenanceRepository;
use App\Modules\Notification\Services\NotificationService;
use Illuminate\Pagination\LengthAwarePaginator;

class MaintenanceService
{
    public function __construct(
        protected MaintenanceRepository $repository,
        protected NotificationService $notificationService,
    ) {}

    public function datatable(array $filters = [])
    {
        return $this->repository->datatable($filters, ['vehicle'], function ($dataTable) {
            $dataTable->addColumn('vehicle_name', fn($m) => $m->vehicle?->full_name ?? '—')
                ->addColumn('status_label', fn($m) => ucfirst($m->status))
                ->addColumn('priority_label', fn($m) => ucfirst($m->priority ?? 'normal'));
        });
    }

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage, $filters, ['vehicle', 'creator']);
    }

    public function find(string $id): Maintenance
    {
        return $this->repository->findByIdOrFail($id, ['vehicle', 'creator']);
    }

    public function create(array $data): Maintenance
    {
        $data['created_by'] = auth('api')->id();
        $maintenance = $this->repository->create($data);

        if (in_array($data['status'] ?? 'scheduled', ['scheduled', 'in_progress'])) {
            $maintenance->vehicle->update(['status' => 'maintenance']);
        }

        $this->notificationService->notifyMaintenanceScheduled($maintenance);

        return $maintenance;
    }

    public function update(string $id, array $data): Maintenance
    {
        return $this->repository->update($id, $data);
    }

    public function delete(string $id): bool
    {
        return $this->repository->delete($id);
    }

    public function complete(string $id, array $data = []): Maintenance
    {
        $maintenance = $this->repository->findByIdOrFail($id);
        $maintenance->update(array_merge($data, [
            'status'          => 'completed',
            'completion_date' => now(),
        ]));
        $maintenance->vehicle->update(['status' => 'available']);
        $maintenance = $maintenance->fresh();

        $this->notificationService->notifyMaintenanceCompleted($maintenance);

        return $maintenance;
    }

    public function cancel(string $id): Maintenance
    {
        $maintenance = $this->repository->findByIdOrFail($id);
        $maintenance->update(['status' => 'cancelled']);
        $maintenance->vehicle->update(['status' => 'available']);
        return $maintenance->fresh();
    }

    public function scheduled(int $perPage = 15): LengthAwarePaginator
    {
        return Maintenance::scheduled()->with('vehicle')->paginate($perPage);
    }

    public function overdue(int $perPage = 15): LengthAwarePaginator
    {
        return Maintenance::overdue()->with('vehicle')->paginate($perPage);
    }
}
