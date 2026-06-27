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
        if (in_array($order->status, ['selesai', 'dibatalkan'], true)) {
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
