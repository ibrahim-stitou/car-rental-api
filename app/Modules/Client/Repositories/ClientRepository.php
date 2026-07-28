<?php

namespace App\Modules\Client\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Models\Client;
use Illuminate\Database\Eloquent\Builder;

class ClientRepository extends BaseRepository
{
    public function __construct(Client $model)
    {
        parent::__construct($model);
    }

    public function getSearchFields(): array
    {
        return ['first_name', 'last_name', 'email', 'phone', 'id_number', 'driving_license_number'];
    }

    protected function applyFilters(Builder $query, array $filters): Builder
    {
        if (!empty($filters['agency_id'])) {
            $agencyId = $filters['agency_id'];
            $query->whereHas('agencies', fn ($q) => $q->where('agencies.id', $agencyId));
            unset($filters['agency_id']);
        }

        if (!empty($filters['search'])) {
            $term = $filters['search'];
            unset($filters['search']);
            // Tokenized so word order doesn't matter (e.g. "Ben Ahmed" still
            // finds a client stored as first_name=Ahmed, last_name=Ben): each
            // word must match somewhere, but independently of the others.
            $tokens = array_filter(preg_split('/\s+/', trim($term)) ?: []);
            $query->where(function (Builder $outer) use ($tokens) {
                foreach ($tokens as $token) {
                    $outer->where(function (Builder $q) use ($token) {
                        foreach ($this->getSearchFields() as $field) {
                            $q->orWhere($field, 'LIKE', "%{$token}%");
                        }
                    });
                }
            });
        }

        return parent::applyFilters($query, $filters);
    }
}

