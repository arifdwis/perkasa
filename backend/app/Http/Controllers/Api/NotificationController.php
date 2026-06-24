<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Notification types grouped by scope.
     */
    private const SCOPE_TYPES = [
        'seller' => ['new_order', 'store_verification', 'new_review'],
        'buyer'  => ['order_status_updated', 'alumni_verification', 'registration'],
    ];

    /**
     * Apply scope filter to a notification query.
     */
    private function applyScope($query, Request $request): void
    {
        $scope = $request->query('scope');

        if ($scope && isset(self::SCOPE_TYPES[$scope])) {
            $query->where(function ($q) use ($scope) {
                foreach (self::SCOPE_TYPES[$scope] as $type) {
                    $q->orWhere('data->type', $type);
                }
            });
        }
    }

    /**
     * Display a listing of user's notifications.
     */
    public function index(Request $request)
    {
        $query = $request->user()->notifications();
        $this->applyScope($query, $request);

        return response()->json($query->paginate(10));
    }

    /**
     * Get the count of unread notifications.
     */
    public function unreadCount(Request $request)
    {
        $query = $request->user()->unreadNotifications();
        $this->applyScope($query, $request);

        return response()->json([
            'unread_count' => $query->count(),
        ]);
    }

    /**
     * Mark a specific notification as read.
     */
    public function markAsRead(Request $request, $id)
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return response()->json([
            'message' => 'Notifikasi berhasil ditandai telah dibaca.',
            'notification' => $notification,
        ]);
    }

    /**
     * Mark all user's notifications as read.
     */
    public function markAllAsRead(Request $request)
    {
        $query = $request->user()->unreadNotifications();
        $this->applyScope($query, $request);
        $query->get()->markAsRead();

        return response()->json([
            'message' => 'Semua notifikasi berhasil ditandai telah dibaca.',
        ]);
    }
}
