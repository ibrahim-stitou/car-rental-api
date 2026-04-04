<?php

namespace App\Policies\Modules\Billing;

use App\Models\BillingDocument;
use App\Models\User;

class BillingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view-billing');
    }

    public function view(User $user, BillingDocument $billing): bool
    {
        return $user->hasPermissionTo('view-billing');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create-billing');
    }

    public function update(User $user, BillingDocument $billing): bool
    {
        return $user->hasPermissionTo('edit-billing');
    }

    public function delete(User $user, BillingDocument $billing): bool
    {
        return $user->hasPermissionTo('delete-billing');
    }

    public function approve(User $user, BillingDocument $billing): bool
    {
        return $user->hasPermissionTo('approve-billing');
    }
}

