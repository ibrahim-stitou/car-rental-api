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

        if (!empty($filters['search'])) {
            $term = $filters['search'];
            unset($filters['search']);
            $query->where(function (Builder $q) use ($term) {
                foreach ($this->getSearchFields() as $field) {
                    $q->orWhere($field, 'LIKE', "%{$term}%");
                }
            });
        }

        // Période : réservations dont le séjour [pickup_date, return_date]
        // chevauche l'intervalle demandé (mêmes bornes que le calendrier,
        // voir ReservationService::calendar()), pas seulement celles qui
        // démarrent dedans.
        if (!empty($filters['date_from'])) {
            $dateFrom = $filters['date_from'];
            unset($filters['date_from']);
            $query->whereDate('return_date', '>=', $dateFrom);
        }

        if (!empty($filters['date_to'])) {
            $dateTo = $filters['date_to'];
            unset($filters['date_to']);
            $query->whereDate('pickup_date', '<=', $dateTo);
        }

        return parent::applyFilters($query, $filters);
    }
}

