<?php

namespace App\Modules\Agency\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Models\Agency;

class AgencyRepository extends BaseRepository
{
    public function __construct(Agency $model)
    {
        parent::__construct($model);
    }

    public function getSearchFields(): array
    {
        return ['name', 'city', 'email', 'phone'];
    }
}

