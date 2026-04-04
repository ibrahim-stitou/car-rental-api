<?php

namespace App\Modules\Vehicle\Services;

use App\Models\Vehicle;
use App\Modules\Vehicle\Repositories\VehicleRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class VehicleService
{
    public function __construct(protected VehicleRepository $repository)
    {
    }

    public function datatable(array $filters = [])
    {
        return $this->repository->datatable($filters, ['agency'], function ($dataTable) {
            $dataTable->addColumn('agency_name', fn($vehicle) => $vehicle->agency?->name ?? '—')
                ->addColumn('full_name', fn($vehicle) => $vehicle->full_name)
                ->addColumn('is_available', fn($vehicle) => $vehicle->is_available ? 'Oui' : 'Non')
                ->filterColumn('agency_name', function ($query, $keyword) {
                    $query->whereHas('agency', fn($q) => $q->where('name', 'LIKE', "%{$keyword}%"));
                });
        });
    }

    public function list(array $filters = [], int $perPage = 15, string $sortBy = 'created_at', string $sortDir = 'desc'): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage, $filters, ['agency'], $sortBy, $sortDir);
    }

    public function search(string $term, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->search($term, $this->repository->getSearchFields(), ['agency'], $perPage);
    }

    public function find(string $id): Vehicle
    {
        return $this->repository->findByIdOrFail($id, ['agency', 'technicalInspections', 'insurances', 'vignettes']);
    }

    public function create(array $data): Vehicle
    {
        return $this->repository->create($data);
    }

    public function update(string $id, array $data): Vehicle
    {
        return $this->repository->update($id, $data);
    }

    public function delete(string $id): bool
    {
        return $this->repository->delete($id);
    }

    public function restore(string $id): Vehicle
    {
        return $this->repository->restore($id);
    }

    public function updateStatus(string $id, string $status): Vehicle
    {
        return $this->repository->update($id, ['status' => $status]);
    }
}
