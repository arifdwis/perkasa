<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Notifications\OrderStatusUpdatedNotification;
use App\Exports\OrderExport;
use App\Exports\SalesExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;

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
     * Get finance/earnings summary for the seller's store.
     */
    public function finance(Request $request)
    {
        $user = $request->user();
        $store = $user->profile?->store;

        if (! $store) {
            return response()->json(['message' => 'Anda tidak memiliki toko.'], 403);
        }

        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        $orderQuery = Order::where('store_id', $store->id);
        if ($dateFrom) {
            $orderQuery->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $orderQuery->whereDate('created_at', '<=', $dateTo);
        }

        $allOrders = $orderQuery->get();
        $completedOrders = $allOrders->whereIn('status', ['selesai', 'diproses', 'dalam_pengantaran']);

        $totalOmzet = (int) $completedOrders->sum('total');
        $totalSubtotal = (int) $completedOrders->sum('subtotal');
        $totalOngkir = (int) $completedOrders->sum('biaya_antar');
        $totalTransaksi = $allOrders->count();
        $totalSelesai = $allOrders->where('status', 'selesai')->count();
        $totalDibatalkan = $allOrders->where('status', 'dibatalkan')->count();
        $pesananAktif = $allOrders->whereIn('status', ['menunggu_konfirmasi', 'diproses', 'dalam_pengantaran'])->count();
        $rata2Order = $totalTransaksi > 0 ? round($totalOmzet / $totalTransaksi) : 0;

        // Total products sold (qty) across completed orders
        $totalProdukTerjual = (int) OrderItem::whereIn('order_id', $completedOrders->pluck('id'))
            ->sum('quantity');

        // Monthly trend (last 12 months) - always full 12 months regardless of filter
        $months = [];
        for ($i = 11; $i >= 0; $i--) {
            $months[] = now()->subMonths($i)->format('Y-m');
        }
        $monthlyTrend = [];
        foreach ($months as $month) {
            $y = substr($month, 0, 4);
            $m = substr($month, 5, 2);
            $monthOrders = Order::where('store_id', $store->id)
                ->where('created_at', '>=', "{$y}-{$m}-01")
                ->where('created_at', '<', now()->setDate((int) $y, (int) $m, 1)->addMonth()->toDateString())
                ->get();
            $monthCompleted = $monthOrders->whereIn('status', ['selesai', 'diproses', 'dalam_pengantaran']);
            $monthlyTrend[] = [
                'bulan' => $month,
                'omzet' => (int) $monthCompleted->sum('total'),
                'pesanan' => $monthOrders->count(),
            ];
        }

        // Top products by revenue
        $topProducts = OrderItem::whereIn('order_id', $completedOrders->pluck('id'))
            ->selectRaw('product_id, name, sum(quantity) as total_qty, sum(price * quantity) as total_revenue')
            ->groupBy('product_id', 'name')
            ->orderByDesc('total_revenue')
            ->limit(10)
            ->get();

        // Recent completed orders
        $recentCompleted = $allOrders->where('status', 'selesai')
            ->sortByDesc('created_at')
            ->take(10)
            ->map(fn ($o) => [
                'id' => $o->id,
                'order_number' => $o->order_number,
                'buyer' => $o->nama_penerima,
                'total' => (int) $o->total,
                'created_at' => $o->created_at->format('Y-m-d H:i:s'),
            ])
            ->values();

        return response()->json([
            'total_omzet' => $totalOmzet,
            'total_subtotal' => $totalSubtotal,
            'total_ongkir' => $totalOngkir,
            'total_transaksi' => $totalTransaksi,
            'total_selesai' => $totalSelesai,
            'total_dibatalkan' => $totalDibatalkan,
            'pesanan_aktif' => $pesananAktif,
            'total_produk_terjual' => $totalProdukTerjual,
            'rata2_order' => $rata2Order,
            'monthly_trend' => $monthlyTrend,
            'top_products' => $topProducts,
            'recent_completed' => $recentCompleted,
        ]);
    }

    /**
     * Export the seller's own sales report (Excel/CSV/PDF).
     */
    public function exportSales(Request $request)
    {
        $user = $request->user();
        $store = $user->profile?->store;

        if (! $store) {
            return response()->json(['message' => 'Anda tidak memiliki toko.'], 403);
        }

        $format = $request->query('format', 'excel');
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        $query = Order::with(['user.profile', 'store.alumniProfile', 'items'])
            ->where('store_id', $store->id)
            ->whereIn('status', ['selesai', 'diproses', 'dalam_pengantaran']);

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $sales = $query->orderBy('created_at', 'desc')->get();
        $fileName = 'laporan_penjualan_' . $store->name . '_' . now()->format('YmdHis');
        $storeName = $store->name;

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('exports.sales', [
                'sales' => $sales,
                'is_pdf' => true,
                'store_name' => $storeName,
            ])->setPaper('a4', 'landscape');

            return $pdf->download($fileName . '.pdf');
        }

        $export = new SalesExport(['store_id' => $store->id, 'date_from' => $dateFrom, 'date_to' => $dateTo]);

        if ($format === 'csv') {
            return Excel::download($export, $fileName . '.csv', \Maatwebsite\Excel\Excel::CSV);
        }

        return Excel::download($export, $fileName . '.xlsx', \Maatwebsite\Excel\Excel::XLSX);
    }

    /**
     * Export the seller's own orders report (Excel/CSV/PDF).
     */
    public function exportOrders(Request $request)
    {
        $user = $request->user();
        $store = $user->profile?->store;

        if (! $store) {
            return response()->json(['message' => 'Anda tidak memiliki toko.'], 403);
        }

        $format = $request->query('format', 'excel');
        $status = $request->query('status');
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        $query = Order::with(['user.profile', 'store.alumniProfile', 'items'])
            ->where('store_id', $store->id);

        if ($status) {
            $query->where('status', $status);
        }
        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $orders = $query->orderBy('created_at', 'desc')->get();
        $fileName = 'laporan_pesanan_' . $store->name . '_' . now()->format('YmdHis');

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('exports.orders', [
                'orders' => $orders,
                'is_pdf' => true,
            ])->setPaper('a4', 'landscape');

            return $pdf->download($fileName . '.pdf');
        }

        $export = new OrderExport(['store_id' => $store->id, 'status' => $status, 'date_from' => $dateFrom, 'date_to' => $dateTo]);

        if ($format === 'csv') {
            return Excel::download($export, $fileName . '.csv', \Maatwebsite\Excel\Excel::CSV);
        }

        return Excel::download($export, $fileName . '.xlsx', \Maatwebsite\Excel\Excel::XLSX);
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
