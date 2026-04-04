<?php

namespace App\Modules\User\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Models\User;

class UserRepository extends BaseRepository
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    public function getSearchFields(): array
    {
        return ['first_name', 'last_name', 'email', 'phone'];
    }
}

