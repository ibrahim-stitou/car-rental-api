<?php

namespace App\Modules\Maintenance\Services;

use App\Models\Expense;
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

        $this->syncExpense($maintenance);

        $this->notificationService->notifyMaintenanceScheduled($maintenance);

        return $maintenance;
    }

    public function update(string $id, array $data): Maintenance
    {
        $maintenance = $this->repository->update($id, $data);
        $this->syncExpense($maintenance);
        return $maintenance;
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

        $this->syncExpense($maintenance);

        $this->notificationService->notifyMaintenanceCompleted($maintenance);

        return $maintenance;
    }

    /**
     * Creates or updates the Expense record linked to this maintenance so that
     * maintenance costs are also reflected as typed expenses.
     */
    private function syncExpense(Maintenance $maintenance): void
    {
        $amount = $maintenance->actual_cost ?? $maintenance->cost;
        if (!$amount || (float) $amount <= 0) {
            return;
        }

        $attributes = [
            'agency_id'    => $maintenance->vehicle?->agency_id,
            'vehicle_id'   => $maintenance->vehicle_id,
            'recorded_by'  => $maintenance->created_by,
            'title'        => 'Maintenance: ' . $maintenance->title,
            'category'     => 'maintenance',
            'amount'       => $amount,
            'expense_date' => $maintenance->completion_date ?? $maintenance->maintenance_date ?? now(),
        ];

        if ($maintenance->expense_id) {
            Expense::where('id', $maintenance->expense_id)->update($attributes);
            return;
        }

        $expense = Expense::create($attributes);
        $maintenance->update(['expense_id' => $expense->id]);
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
