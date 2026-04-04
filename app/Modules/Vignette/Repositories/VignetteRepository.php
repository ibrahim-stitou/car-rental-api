<?php

namespace App\Modules\Vignette\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Models\Vignette;

class VignetteRepository extends BaseRepository
{
    public function __construct(Vignette $model)
    {
        parent::__construct($model);
    }

    public function getSearchFields(): array
    {
        return ['payment_reference'];
    }
}

