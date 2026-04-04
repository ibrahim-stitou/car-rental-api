<?php

namespace App\Modules\Vehicle\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Models\Vehicle;

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
}

