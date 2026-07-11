<?php

namespace App\Modules\Parameter\Services;

use App\Models\Parameter;
use App\Modules\Parameter\Repositories\ParameterRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ParameterService
{
    public function __construct(protected ParameterRepository $repository)
    {
    }

    public function list(array $filters = [], int $perPage = 100, string $sortBy = 'sort_order', string $sortDir = 'asc'): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage, $filters, [], $sortBy, $sortDir);
    }

    public function allForCategory(string $category, bool $activeOnly = true): Collection
    {
        $query = Parameter::query()->category($category)->orderBy('sort_order')->orderBy('label');
        if ($activeOnly) {
            $query->active();
        }
        return $query->get();
    }

    public function find(string $id): Parameter
    {
        return $this->repository->findByIdOrFail($id);
    }

    public function create(array $data): Parameter
    {
        $parameter = $this->repository->create($data);
        return $parameter->fresh();
    }

    public function update(string $id, array $data): Parameter
    {
        return $this->repository->update($id, $data);
    }

    public function delete(string $id): bool
    {
        return $this->repository->delete($id);
    }

    /**
     * Returns a human-readable label of what references this parameter's value, or null if unused.
     */
    public function usageLabel(Parameter $parameter): ?string
    {
        $count = match ($parameter->category) {
            'insurance_type'    => \App\Models\Insurance::where('type', $parameter->value)->count(),
            'insurance_company' => \App\Models\Insurance::where('insurance_company', $parameter->value)->count(),
            'inspection_center' => \App\Models\TechnicalInspection::where('inspection_center', $parameter->value)->count(),
            'expense_category'  => \App\Models\Expense::where('category', $parameter->value)->count(),
            default             => 0,
        };

        return $count > 0 ? "Utilisé par {$count} enregistrement(s)" : null;
    }
}
