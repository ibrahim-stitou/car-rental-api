<?php

namespace App\Modules\Maintenance\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Models\Maintenance;
use Illuminate\Database\Eloquent\Builder;

class MaintenanceRepository extends BaseRepository
{
    public function __construct(Maintenance $model)
    {
        parent::__construct($model);
    }

    public function getSearchFields(): array
    {
        return ['description', 'service_provider'];
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

