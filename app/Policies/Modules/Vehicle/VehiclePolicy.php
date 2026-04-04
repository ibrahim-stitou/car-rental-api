<?php

namespace App\Policies\Modules\Vehicle;

use App\Models\User;
use App\Models\Vehicle;

class VehiclePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view-vehicle');
    }

    public function view(User $user, Vehicle $vehicle): bool
    {
        return $user->hasPermissionTo('view-vehicle');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create-vehicle');
    }

    public function update(User $user, Vehicle $vehicle): bool
    {
        return $user->hasPermissionTo('edit-vehicle');
    }

    public function delete(User $user, Vehicle $vehicle): bool
    {
        return $user->hasPermissionTo('delete-vehicle');
    }
}

