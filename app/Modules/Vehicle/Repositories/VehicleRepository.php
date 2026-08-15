<?php

namespace App\Modules\Vehicle\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Builder;

class VehicleRepository extends BaseRepository
{
    public function __construct(Vehicle $model)
    {
        parent::__construct($model);
    }

    public function getSearchFields(): array
    {
        return ['brand', 'model', 'registration_number', 'vin', 'color'];
    }

    protected function applyFilters(Builder $query, array $filters): Builder
    {
        // Vehicles currently out on an active rental due back today —
        // not a plain column, so it needs a relation query rather than the
        // generic equality matching in BaseRepository::applyFilters().
        if (array_key_exists('returning_today', $filters)) {
            $wantsReturningToday = filter_var($filters['returning_today'], FILTER_VALIDATE_BOOLEAN);
            unset($filters['returning_today']);
            if ($wantsReturningToday) {
                $query->whereHas('reservations', function (Builder $q) {
                    $q->where('status', 'active')->whereDate('return_date', today());
                });
            }
        }

        return parent::applyFilters($query, $filters);
    }
}

