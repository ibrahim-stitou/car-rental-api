<?php

namespace App\Modules\Insurance\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Models\Insurance;
use Illuminate\Database\Eloquent\Builder;

class InsuranceRepository extends BaseRepository
{
    public function __construct(Insurance $model)
    {
        parent::__construct($model);
    }

    public function getSearchFields(): array
    {
        return ['insurance_company', 'policy_number', 'agent_name', 'notes'];
    }

    protected function applyFilters($query, array $filters): Builder
    {
        if (!empty($filters['vehicule'])) {
            $registrationNumber = $filters['vehicule'];

            unset($filters['vehicule']);

            $query->whereHas('vehicle', function ($q) use ($registrationNumber) {
                $q->where('registration_number', 'LIKE', "%{$registrationNumber}%");
            });
        }

        return parent::applyFilters($query, $filters);
    }
}

