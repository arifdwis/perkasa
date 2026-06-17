<?php

namespace App\Policies;

use App\Models\AlumniProfile;
use App\Models\User;

class AlumniProfilePolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, AlumniProfile $profile): bool
    {
        return $user->id === $profile->user_id || $user->hasPermissionTo('view_alumni_list');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, AlumniProfile $profile): bool
    {
        // Users can update their own profile, administrators with verify_alumni permission can edit/verify profiles
        return $user->id === $profile->user_id || $user->hasPermissionTo('verify_alumni');
    }
}
