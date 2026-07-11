<?php

namespace App\Modules\Agency\Services;

use App\Models\Agency;
use App\Modules\Agency\Repositories\AgencyRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class AgencyService
{
    public function __construct(protected AgencyRepository $repository)
    {
    }

    public function datatable(array $filters = [])
    {
        return $this->repository->datatable($filters, ['manager'], function ($dataTable) {
            $dataTable->addColumn('manager_name', fn($agency) => $agency->manager?->full_name ?? '—')
                ->addColumn('vehicles_count', fn($agency) => $agency->vehicles()->count())
                ->filterColumn('manager_name', function ($query, $keyword) {
                    $query->whereHas('manager', function ($q) use ($keyword) {
                        $q->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$keyword}%"]);
                    });
                });
        });
    }

    public function list(array $filters = [], int $perPage = 15, string $sortBy = 'created_at', string $sortDir = 'desc'): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage, $filters, ['manager'], $sortBy, $sortDir, ['vehicles']);
    }

    public function search(string $term, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->search($term, $this->repository->getSearchFields(), ['manager'], $perPage);
    }

    public function find(string $id): Agency
    {
        return $this->repository->findByIdOrFail($id, ['manager', 'vehicles']);
    }

    public function create(array $data): Agency
    {
        return $this->repository->create($data);
    }

    public function update(string $id, array $data): Agency
    {
        return $this->repository->update($id, $data);
    }

    public function delete(string $id): bool
    {
        return $this->repository->delete($id);
    }

    public function restore(string $id): Agency
    {
        return $this->repository->restore($id);
    }
}
