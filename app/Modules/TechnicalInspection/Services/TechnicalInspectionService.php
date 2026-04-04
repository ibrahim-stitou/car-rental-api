<?php

namespace App\Modules\TechnicalInspection\Services;

use App\Models\TechnicalInspection;
use App\Modules\TechnicalInspection\Repositories\TechnicalInspectionRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class TechnicalInspectionService
{
    public function __construct(protected TechnicalInspectionRepository $repository) {}

    public function list(array $filters = [], int $perPage = 15, string $sortBy = 'created_at', string $sortDir = 'desc'): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage, $filters, ['vehicle', 'creator'], $sortBy, $sortDir);
    }

    public function find(string $id): TechnicalInspection
    {
        return $this->repository->findByIdOrFail($id, ['vehicle', 'creator']);
    }

    public function create(array $data): TechnicalInspection
    {
        $data['created_by'] = auth('api')->id();
        return $this->repository->create($data);
    }

    public function update(string $id, array $data): TechnicalInspection
    {
        return $this->repository->update($id, $data);
    }

    public function delete(string $id): bool
    {
        return $this->repository->delete($id);
    }

    public function expired(int $perPage = 15): LengthAwarePaginator
    {
        return TechnicalInspection::expired()->with('vehicle')->paginate($perPage);
    }

    public function expiringSoon(int $days = 30, int $perPage = 15): LengthAwarePaginator
    {
        return TechnicalInspection::expiringSoon($days)->with('vehicle')->paginate($perPage);
    }

    public function datatable(array $filters = [])
    {
        return $this->repository->datatable($filters, ['vehicle'], function ($dataTable) {
            $dataTable->addColumn('vehicle_name', fn($t) => $t->vehicle?->full_name ?? '—')
                ->addColumn('result_label', fn($t) => ucfirst($t->result))
                ->addColumn('days_until_expiry', fn($t) => $t->expiry_date ? now()->diffInDays($t->expiry_date, false) : null);
        });
    }
}
