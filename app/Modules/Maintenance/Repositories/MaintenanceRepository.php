<?php

namespace App\Modules\Maintenance\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Models\Maintenance;

class MaintenanceRepository extends BaseRepository
{
    public function __construct(Maintenance $model)
    {
        parent::__construct($model);
    }

    public function getSearchFields(): array
    {
        return ['description', 'service_provider'];
    }
}

