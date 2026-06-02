<?php

namespace App\Modules\Claim\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Models\Claim;

class ClaimRepository extends BaseRepository
{
    public function __construct(Claim $model)
    {
        parent::__construct($model);
    }

    public function getSearchFields(): array
    {
        return ['claim_number', 'title', 'description', 'insurance_reference'];
    }
}
