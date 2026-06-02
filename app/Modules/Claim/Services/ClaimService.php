<?php

namespace App\Modules\Claim\Services;

use App\Models\Claim;
use App\Modules\Claim\Repositories\ClaimRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class ClaimService
{
    public function __construct(protected ClaimRepository $repository) {}

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate(
            $perPage,
            $filters,
            ['vehicle:id,brand,model,registration_number', 'client:id,first_name,last_name', 'creator:id,first_name,last_name'],
            'claim_date',
            'desc'
        );
    }

    public function search(string $term, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->search(
            $term,
            $this->repository->getSearchFields(),
            ['vehicle:id,brand,model,registration_number', 'client:id,first_name,last_name'],
            $perPage
        );
    }

    public function find(string $id): Claim
    {
        return $this->repository->findByIdOrFail($id, [
            'vehicle', 'client', 'reservation', 'maintenance', 'creator',
        ]);
    }

    public function create(array $data): Claim
    {
        return $this->repository->create($data);
    }

    public function update(string $id, array $data): Claim
    {
        return $this->repository->update($id, $data);
    }

    public function delete(string $id): bool
    {
        return $this->repository->delete($id);
    }

    public function updateStatus(string $id, string $status): Claim
    {
        return $this->repository->update($id, ['status' => $status]);
    }

    public function statistics(): array
    {
        $query = Claim::query();
        return [
            'total'                    => (clone $query)->count(),
            'open'                     => (clone $query)->open()->count(),
            'settled'                  => (clone $query)->where('status', 'settled')->count(),
            'total_damage'             => (float) (clone $query)->sum('total_damage_amount'),
            'total_insurance_recovered'=> (float) (clone $query)->sum('insurance_amount_recovered'),
            'total_client_paid'        => (float) (clone $query)->sum('client_paid_amount'),
            'total_company_expense'    => (float) (clone $query)->sum('company_expense_amount'),
        ];
    }
}
