<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Notifications\OrderStatusUpdatedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class SellerOrderController extends Controller
{
    /**
     * List all orders received by the seller's store.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $store = $user->profile?->store;

        if (! $store) {
            return response()->json(['message' => 'Anda tidak memiliki toko.'], 403);
        }

        $status = $request->query('status');
        $search = $request->query('search');

        $query = Order::where('store_id', $store->id)
            ->with(['user', 'items.product.primaryImage'])
            ->orderBy('created_at', 'desc');

        if ($status) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('nama_penerima', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        return response()->json($query->paginate(15));
    }

    /**
     * Get order counts grouped by status for the seller's store.
     */
    public function stats(Request $request)
    {
        $user = $request->user();
        $store = $user->profile?->store;

        if (! $store) {
            return response()->json(['message' => 'Anda tidak memiliki toko.'], 403);
        }

        $counts = Order::where('store_id', $store->id)
            ->selectRaw("status, count(*) as total")
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn ($v) => (int) $v)
            ->all();

        $total = array_sum($counts);

        return response()->json([
            'total' => (int) $total,
            'menunggu_konfirmasi' => (int) ($counts['menunggu_konfirmasi'] ?? 0),
            'diproses' => (int) ($counts['diproses'] ?? 0),
            'dalam_pengantaran' => (int) ($counts['dalam_pengantaran'] ?? 0),
            'selesai' => (int) ($counts['selesai'] ?? 0),
            'dibatalkan' => (int) ($counts['dibatalkan'] ?? 0),
        ]);
    }

    /**
     * Show details of an order received by the seller's store.
     */
    public function show(Request $request, $id)
    {
        $order = Order::with([
            'user.profile',
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
     * Update the status of an order (diproses, dalam_pengantaran, selesai, dibatalkan).
     */
    public function updateStatus(Request $request, $id)
    {
        $order = Order::with('items.product')->findOrFail($id);

        Gate::authorize('updateStatus', $order);

        $request->validate([
            'status' => ['required', 'string', 'in:diproses,dalam_pengantaran,selesai,dibatalkan'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $currentStatus = $order->status;
        $newStatus = $request->status;

        // Strict state machine transitions check
        $valid = false;
        if ($currentStatus === 'menunggu_konfirmasi') {
            $valid = in_array($newStatus, ['diproses', 'dibatalkan'], true);
        } elseif ($currentStatus === 'diproses') {
            $valid = in_array($newStatus, ['dalam_pengantaran', 'dibatalkan'], true);
        } elseif ($currentStatus === 'dalam_pengantaran') {
            $valid = ($newStatus === 'selesai');
        }

        if (! $valid) {
            return response()->json([
                'message' => "Transisi status pesanan dari {$currentStatus} ke {$newStatus} tidak diperbolehkan.",
            ], 400);
        }

        // Setup default log descriptions if none provided
        $description = $request->description;
        if (! $description) {
            switch ($newStatus) {
                case 'diproses':
                    $description = 'Pesanan dikonfirmasi dan sedang diproses oleh penjual.';
                    break;
                case 'dalam_pengantaran':
                    $description = 'Pesanan sedang dikirim oleh kurir penjual.';
                    break;
                case 'selesai':
                    $description = 'Pesanan selesai diterima dan dibayar secara COD.';
                    break;
                case 'dibatalkan':
                    $description = 'Pesanan dibatalkan oleh penjual.';
                    break;
            }
        }

        try {
            DB::transaction(function () use ($order, $newStatus, $description, $request) {
                $order->update(['status' => $newStatus]);

                $order->statusLogs()->create([
                    'status' => $newStatus,
                    'description' => $description,
                    'changed_by' => $request->user()->id,
                ]);

                // Record to Spatie Activity Log
                activity()
                    ->performedOn($order)
                    ->log("Status pesanan {$order->order_number} diperbarui menjadi '{$newStatus}' oleh penjual.");

                // If cancelled by seller, restore stocks
                if ($newStatus === 'dibatalkan') {
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
                }
            });

            // Notify buyer of status update
            $buyer = $order->user;
            if ($buyer) {
                $buyer->notify(new OrderStatusUpdatedNotification($order, $currentStatus, $newStatus));
            }

            return response()->json([
                'message' => 'Status pesanan berhasil diperbarui.',
                'order' => $order->fresh(['statusLogs']),
            ]);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal memperbarui status pesanan.'], 500);
        }
    }
}
