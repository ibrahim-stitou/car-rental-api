<?php

namespace App\Modules\Parameter\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Models\Parameter;

class ParameterRepository extends BaseRepository
{
    public function __construct(Parameter $model)
    {
        parent::__construct($model);
    }

    public function getSearchFields(): array
    {
        return ['label', 'value'];
    }
}
