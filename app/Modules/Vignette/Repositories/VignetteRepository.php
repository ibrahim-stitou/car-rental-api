<?php

namespace App\Modules\Vignette\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Models\Vignette;
use Illuminate\Database\Eloquent\Builder;

class VignetteRepository extends BaseRepository
{
    public function __construct(Vignette $model)
    {
        parent::__construct($model);
    }

    public function getSearchFields(): array
    {
        return ['payment_reference'];
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

