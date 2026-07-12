<?php

namespace App\Modules\Reservation\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Models\Reservation;
use Illuminate\Database\Eloquent\Builder;

class ReservationRepository extends BaseRepository
{
    public function __construct(Reservation $model)
    {
        parent::__construct($model);
    }

    public function getSearchFields(): array
    {
        return ['reservation_number', 'pickup_location', 'return_location', 'notes'];
    }

    protected function applyFilters(Builder $query, array $filters): Builder
    {
        if (!empty($filters['overdue'])) {
            unset($filters['overdue']);
            $query->overdue();
        }

        return parent::applyFilters($query, $filters);
    }
}

