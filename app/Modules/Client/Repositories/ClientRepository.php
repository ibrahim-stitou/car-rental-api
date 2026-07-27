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
        return parent::applyFilters($query, $filters);
    }
}

