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
        // 1. Recherche globale sur la réservation, le véhicule et le client
        if (!empty($filters['search'])) {
            $term = $filters['search'];
            unset($filters['search']);

            $query->where(function (Builder $q) use ($term) {
                // Champs propres à la réservation
                foreach ($this->getSearchFields() as $field) {
                    $q->orWhere($field, 'LIKE', "%{$term}%");
                }

                // Recherche dans le véhicule lié (Immatriculation, Marque, Modèle, VIN)
                $q->orWhereHas('vehicle', function (Builder $vq) use ($term) {
                    $vq->where('registration_number', 'LIKE', "%{$term}%");
                });

                // Recherche dans le client lié (Nom, Prénom, Téléphone, CIN)
                $q->orWhereHas('client', function (Builder $cq) use ($term) {
                    $cq->where('first_name', 'LIKE', "%{$term}%")
                        ->orWhere('last_name', 'LIKE', "%{$term}%")
                        ->orWhere('id_number', 'LIKE', "%{$term}%");
                });
            });
        }

        // 2. Gestion spécifique du filtre Overdue
        if (array_key_exists('overdue', $filters)) {
            $isOverdue = filter_var($filters['overdue'], FILTER_VALIDATE_BOOLEAN);
            unset($filters['overdue']);
            if ($isOverdue) {
                $query->overdue();
            }
        }

        // 3. Gestion spécifique du filtre Legacy ID
        if (array_key_exists('legacy_id', $filters)) {
            $isLegacy = filter_var($filters['legacy_id'], FILTER_VALIDATE_BOOLEAN);
            unset($filters['legacy_id']);
            if ($isLegacy) {
                $query->whereNotNull('legacy_id');
            }
        }

        // 4. Dates : si seule la date de fin est fournie, on veut les réservations
        // dont le retour tombe exactement ce jour-là (pas un chevauchement de
        // période) — c'est le cas d'usage "qu'est-ce qui revient tel jour ?".
        // Si les deux dates sont fournies, on garde la recherche par période
        // (chevauchement pickup_date <= date_to ET return_date >= date_from).
        $hasDateFrom = !empty($filters['date_from']);
        $hasDateTo   = !empty($filters['date_to']);

        if ($hasDateTo && !$hasDateFrom) {
            $query->whereDate('return_date', '=', $filters['date_to']);
            unset($filters['date_to']);
        } else {
            if ($hasDateFrom) {
                $query->whereDate('return_date', '>=', $filters['date_from']);
            }
            if ($hasDateTo) {
                $query->whereDate('pickup_date', '<=', $filters['date_to']);
            }
            unset($filters['date_from'], $filters['date_to']);
        }

        // 5. Délègue le reste (agency_id, vehicle_id, client_id, status, payment_status, etc.) au BaseRepository
        return parent::applyFilters($query, $filters);
    }
}
