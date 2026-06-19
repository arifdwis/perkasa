<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    /**
     * Determine whether the user can view the order.
     */
    public function view(User $user, Order $order): bool
    {
        // Buyer can view
        if ($order->user_id === $user->id) {
            return true;
        }

        // Seller can view if it's their store
        return $order->store->alumniProfile->user_id === $user->id;
    }

    /**
     * Determine whether the user can update the order status.
     */
    public function updateStatus(User $user, Order $order): bool
    {
        // Only the seller can update status
        return $order->store->alumniProfile->user_id === $user->id;
    }

    /**
     * Determine whether the user can cancel the order.
     */
    public function cancel(User $user, Order $order): bool
    {
        // If order is already completed or cancelled, nobody can cancel it
        if (in_slice($order->status, ['selesai', 'dibatalkan'])) {
            return false;
        }

        // If user is the buyer, they can cancel ONLY in 'menunggu_konfirmasi'
        if ($order->user_id === $user->id) {
            return $order->status === 'menunggu_konfirmasi';
        }

        // If user is the seller, they can cancel before completion
        return $order->store->alumniProfile->user_id === $user->id;
    }
}

// Simple helper function if in_slice doesn't exist
if (! function_exists('in_slice')) {
    function in_slice($needle, array $haystack): bool
    {
        return in_array($needle, $haystack, true);
    }
}
