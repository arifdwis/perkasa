<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    /**
     * Determine whether the user can create products.
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
     * Determine whether the user can update the product.
     */
    public function update(User $user, Product $product): bool
    {
        return $product->store->alumniProfile->user_id === $user->id;
    }

    /**
     * Determine whether the user can delete the product.
     */
    public function delete(User $user, Product $product): bool
    {
        return $product->store->alumniProfile->user_id === $user->id;
    }
}
