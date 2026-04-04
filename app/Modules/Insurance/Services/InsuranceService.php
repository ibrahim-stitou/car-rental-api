<?php

namespace App\Modules\Insurance\Services;

use App\Models\Insurance;
use App\Modules\Insurance\Repositories\InsuranceRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class InsuranceService
{
    public function __construct(protected InsuranceRepository $repository) {}

    public function datatable(array $filters = [])
    {
        return $this->repository->datatable($filters, ['vehicle'], function ($dataTable) {
            $dataTable->addColumn('vehicle_name', fn($i) => $i->vehicle?->full_name ?? '—')
                ->addColumn('days_until_expiry', fn($i) => $i->end_date ? now()->diffInDays($i->end_date, false) : null)
                ->addColumn('is_active_status', fn($i) => $i->is_active ? 'Actif' : 'Inactif');
        });
    }

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage, $filters, ['vehicle', 'creator']);
    }

    public function find(string $id): Insurance
    {
        return $this->repository->findByIdOrFail($id, ['vehicle', 'creator']);
    }

    public function create(array $data): Insurance
    {
        $data['created_by'] = auth('api')->id();
        return $this->repository->create($data);
    }

    public function update(string $id, array $data): Insurance
    {
        return $this->repository->update($id, $data);
    }

    public function delete(string $id): bool
    {
        return $this->repository->delete($id);
    }

    public function expired(int $perPage = 15): LengthAwarePaginator
    {
        return Insurance::expired()->with('vehicle')->paginate($perPage);
    }

    public function expiringSoon(int $days = 30, int $perPage = 15): LengthAwarePaginator
    {
        return Insurance::expiringSoon($days)->with('vehicle')->paginate($perPage);
    }
}
