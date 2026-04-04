<?php

namespace App\Modules\TechnicalInspection\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Models\TechnicalInspection;

class TechnicalInspectionRepository extends BaseRepository
{
    public function __construct(TechnicalInspection $model)
    {
        parent::__construct($model);
    }

    public function getSearchFields(): array
    {
        return ['inspection_center', 'inspector_name', 'observations'];
    }
}

