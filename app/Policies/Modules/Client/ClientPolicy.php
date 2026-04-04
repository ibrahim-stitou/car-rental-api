<?php

namespace App\Policies\Modules\Client;

use App\Models\Client;
use App\Models\User;

class ClientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view-client');
    }

    public function view(User $user, Client $client): bool
    {
        return $user->hasPermissionTo('view-client');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create-client');
    }

    public function update(User $user, Client $client): bool
    {
        return $user->hasPermissionTo('edit-client');
    }

    public function delete(User $user, Client $client): bool
    {
        return $user->hasPermissionTo('delete-client');
    }

    public function blacklist(User $user, Client $client): bool
    {
        return $user->hasPermissionTo('blacklist-client');
    }
}

