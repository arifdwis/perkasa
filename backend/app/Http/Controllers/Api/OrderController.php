<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Notifications\OrderStatusUpdatedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class OrderController extends Controller
{
    /**
     * List all orders placed by the authenticated user.
     */
    public function index(Request $request)
    {
        $status = $request->query('status');

        $query = $request->user()->orders()
            ->with(['store', 'items.product.primaryImage'])
            ->orderBy('created_at', 'desc');

        if ($status) {
            $query->where('status', $status);
        }

        return response()->json($query->paginate(15));
    }

    /**
     * Show details of a specific order.
     */
    public function show(Request $request, $id)
    {
        $order = Order::with([
            'store.alumniProfile.user',
            'items.product.primaryImage',
            'items.review',
            'statusLogs.changer',
        ])->findOrFail($id);

        Gate::authorize('view', $order);

        return response()->json([
            'order' => $order,
        ]);
    }

    /**
     * Cancel the order (Allowed only when status is 'menunggu_konfirmasi').
     */
    public function cancel(Request $request, $id)
    {
        $order = Order::with('items.product')->findOrFail($id);

        Gate::authorize('cancel', $order);

        if ($order->status !== 'menunggu_konfirmasi') {
            return response()->json([
                'message' => 'Pesanan tidak dapat dibatalkan karena sudah diproses atau selesai.',
            ], 400);
        }

        try {
            DB::transaction(function () use ($order, $request) {
                $order->update(['status' => 'dibatalkan']);

                $order->statusLogs()->create([
                    'status' => 'dibatalkan',
                    'description' => 'Pesanan dibatalkan oleh pembeli.',
                    'changed_by' => $request->user()->id,
                ]);

                // Record to Spatie Activity Log
                activity()
                    ->performedOn($order)
                    ->log("Pesanan dengan nomor {$order->order_number} dibatalkan oleh pembeli.");

                // Restore stock for all products
                foreach ($order->items as $item) {
                    $product = $item->product;
                    if ($product) {
                        $newStock = $product->stock + $item->quantity;
                        $product->update([
                            'stock' => $newStock,
                            'status' => ($product->status === 'out_of_stock' && $newStock > 0) ? 'active' : $product->status,
                        ]);
                    }
                }
            });

            // Notify seller of cancellation
            $seller = $order->store->alumniProfile?->user;
            if ($seller) {
                $seller->notify(new OrderStatusUpdatedNotification($order, 'menunggu_konfirmasi', 'dibatalkan'));
            }

            return response()->json([
                'message' => 'Pesanan berhasil dibatalkan.',
                'order' => $order->fresh(['statusLogs']),
            ]);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal membatalkan pesanan.'], 500);
        }
    }
}
