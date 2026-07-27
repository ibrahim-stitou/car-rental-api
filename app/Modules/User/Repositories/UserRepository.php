<?php

namespace App\Modules\User\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class UserRepository extends BaseRepository
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    public function getSearchFields(): array
    {
        return ['first_name', 'last_name', 'email', 'phone'];
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

