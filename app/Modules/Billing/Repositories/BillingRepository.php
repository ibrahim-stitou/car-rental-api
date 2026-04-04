<?php

namespace App\Modules\Billing\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Models\BillingDocument;

class BillingRepository extends BaseRepository
{
    public function __construct(BillingDocument $model)
    {
        parent::__construct($model);
    }

    public function getSearchFields(): array
    {
        return ['document_number', 'client_name', 'client_email', 'notes'];
    }
}
