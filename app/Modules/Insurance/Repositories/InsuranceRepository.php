<?php

namespace App\Modules\Insurance\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Models\Insurance;

class InsuranceRepository extends BaseRepository
{
    public function __construct(Insurance $model)
    {
        parent::__construct($model);
    }

    public function getSearchFields(): array
    {
        return ['insurance_company', 'policy_number', 'agent_name', 'notes'];
    }
}

