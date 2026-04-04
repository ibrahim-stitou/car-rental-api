<?php

namespace App\Policies\Modules\Reservation;

use App\Models\Reservation;
use App\Models\User;

class ReservationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view-reservation');
    }

    public function view(User $user, Reservation $reservation): bool
    {
        return $user->hasPermissionTo('view-reservation');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create-reservation');
    }

    public function update(User $user, Reservation $reservation): bool
    {
        return $user->hasPermissionTo('edit-reservation');
    }

    public function delete(User $user, Reservation $reservation): bool
    {
        return $user->hasPermissionTo('delete-reservation');
    }

    public function confirm(User $user, Reservation $reservation): bool
    {
        return $user->hasPermissionTo('confirm-reservation');
    }

    public function activate(User $user, Reservation $reservation): bool
    {
        return $user->hasPermissionTo('activate-reservation');
    }

    public function complete(User $user, Reservation $reservation): bool
    {
        return $user->hasPermissionTo('complete-reservation');
    }

    public function cancel(User $user, Reservation $reservation): bool
    {
        return $user->hasPermissionTo('cancel-reservation');
    }
}

