<?php

namespace App\Policies;

use App\Models\Service;
use App\Models\User;

class ServicePolicy
{
    /**
     * Determine whether the user can create services.
     */
    public function create(User $user): bool
    {
        $profile = $user->profile;
        if (!$profile || !$profile->store) {
            return false;
        }

        return $profile->store->status === 'active';
    }

    /**
     * Determine whether the user can update the service.
     */
    public function update(User $user, Service $service): bool
    {
        return $service->store->alumniProfile->user_id === $user->id;
    }

    /**
     * Determine whether the user can delete the service.
     */
    public function delete(User $user, Service $service): bool
    {
        return $service->store->alumniProfile->user_id === $user->id;
    }
}
