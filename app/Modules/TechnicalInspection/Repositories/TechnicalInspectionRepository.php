<?php

namespace App\Modules\TechnicalInspection\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Models\TechnicalInspection;
use Illuminate\Database\Eloquent\Builder;

class TechnicalInspectionRepository extends BaseRepository
{
    public function __construct(TechnicalInspection $model)
    {
        parent::__construct($model);
    }

    public function getSearchFields(): array
    {
        return [
            'inspection_center',
            'inspector_name',
            'observations',
        ];
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
