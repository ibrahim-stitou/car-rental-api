<?php

namespace App\Modules\Claim\Services;

use App\Models\Claim;
use App\Models\Expense;
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
        $claim = $this->repository->create($data);
        $this->syncExpense($claim);
        return $claim;
    }

    public function update(string $id, array $data): Claim
    {
        $claim = $this->repository->update($id, $data);
        $this->syncExpense($claim);
        return $claim;
    }

    public function delete(string $id): bool
    {
        return $this->repository->delete($id);
    }

    public function updateStatus(string $id, string $status): Claim
    {
        return $this->repository->update($id, ['status' => $status]);
    }

    /**
     * Creates or updates the Expense record linked to this claim so that
     * the company's share of the damage cost is also reflected as a typed expense.
     */
    private function syncExpense(Claim $claim): void
    {
        $amount = $claim->company_expense_amount;
        if (!$amount || (float) $amount <= 0) {
            return;
        }

        $attributes = [
            'agency_id'    => $claim->vehicle?->agency_id,
            'vehicle_id'   => $claim->vehicle_id,
            'recorded_by'  => $claim->created_by,
            'title'        => 'Sinistre: ' . $claim->title,
            'category'     => 'claim',
            'amount'       => $amount,
            'expense_date' => $claim->claim_date ?? now(),
        ];

        if ($claim->expense_id) {
            Expense::where('id', $claim->expense_id)->update($attributes);
            return;
        }

        $expense = Expense::create($attributes);
        $claim->update(['expense_id' => $expense->id]);
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
