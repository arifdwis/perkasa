<?php

namespace App\Policies;

use App\Models\Store;
use App\Models\User;

class StorePolicy
{
    /**
     * Determine whether the user can view the store.
     */
    public function view(User $user, Store $store): bool
    {
        // Owner, super admin, admin_marketplace can view any store state.
        // Active store is viewable by anyone (this is checked in controller, policy handles protected states).
        if ($store->alumniProfile->user_id === $user->id) {
            return true;
        }

        return $user->hasRole('super_admin') || $user->hasRole('admin_marketplace') || $user->hasPermissionTo('view_stores');
    }

    /**
     * Determine whether the user can update the store.
     */
    public function update(User $user, Store $store): bool
    {
        return $store->alumniProfile->user_id === $user->id;
    }
}
