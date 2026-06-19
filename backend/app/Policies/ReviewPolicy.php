<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;

class ReviewPolicy
{
    /**
     * Determine whether the user can create a review.
     */
    public function create(User $user): bool
    {
        // Only verified alumni can write reviews
        return $user->profile?->status_verifikasi === 'verified';
    }

    /**
     * Determine whether the user can reply to the review.
     */
    public function reply(User $user, Review $review): bool
    {
        // Only the owner of the store associated with the review can reply
        $store = $user->profile?->store;

        return $store && $review->store_id === $store->id;
    }
}
