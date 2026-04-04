<?php

namespace App\Modules\Client\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Models\Client;

class ClientRepository extends BaseRepository
{
    public function __construct(Client $model)
    {
        parent::__construct($model);
    }

    public function getSearchFields(): array
    {
        return ['first_name', 'last_name', 'email', 'phone', 'id_number', 'driving_license_number'];
    }
}

