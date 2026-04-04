<?php

namespace App\Policies\Modules\Agency;

use App\Models\Agency;
use App\Models\User;

class AgencyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view-agency');
    }

    public function view(User $user, Agency $agency): bool
    {
        return $user->hasPermissionTo('view-agency');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create-agency');
    }

    public function update(User $user, Agency $agency): bool
    {
        return $user->hasPermissionTo('edit-agency');
    }

    public function delete(User $user, Agency $agency): bool
    {
        return $user->hasPermissionTo('delete-agency');
    }
}

