<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Service;
use App\Models\Store;
use Illuminate\Http\Request;

class AdminFinanceController extends Controller
{
    /**
     * Platform-wide financial summary with detailed breakdowns.
     */
    public function summary(Request $request)
    {
        $query = Order::query();
        $this->applyDateFilter($query, $request);

        $allOrders = (clone $query)->get();

        $completedQuery = (clone $query)->whereIn('status', ['selesai', 'diproses', 'dalam_pengantaran']);
        $completedOrders = $completedQuery->get();

        $totalOmzet = $completedOrders->sum('total');
        $totalSubtotal = $completedOrders->sum('subtotal');
        $totalBiayaAntar = $completedOrders->sum('biaya_antar');
        $totalTransaksi = $allOrders->count();
        $totalSelesai = $allOrders->where('status', 'selesai')->count();
        $totalDibatalkan = $allOrders->where('status', 'dibatalkan')->count();
        $rata2Order = $totalTransaksi > 0 ? round($totalOmzet / $totalTransaksi, 0) : 0;

        $breakdownStatus = $allOrders
            ->groupBy('status')
            ->map(fn ($orders, $status) => [
                'jumlah' => $orders->count(),
                'nominal' => (int) $orders->sum('total'),
            ])
            ->toArray();

        $paymentBreakdown = $completedOrders
            ->groupBy('payment_method')
            ->map(fn ($orders, $method) => [
                'jumlah' => $orders->count(),
                'nominal' => (int) $orders->sum('total'),
            ])
            ->toArray();

        $topProducts = OrderItem::whereIn('order_id', $completedOrders->pluck('id'))
            ->selectRaw('product_id, name, sum(quantity) as total_qty, sum(price * quantity) as total_revenue')
            ->groupBy('product_id', 'name')
            ->orderByDesc('total_revenue')
            ->limit(10)
            ->get();

        $topBuyers = $allOrders
            ->groupBy('user_id')
            ->map(fn ($orders) => [
                'user_id' => $orders->first()->user_id,
                'total_orders' => $orders->count(),
                'total_spent' => (int) $orders->whereIn('status', ['selesai', 'diproses', 'dalam_pengantaran'])->sum('total'),
            ])
            ->sortByDesc('total_spent')
            ->take(10)
            ->values();

        $topBuyerIds = $topBuyers->pluck('user_id')->filter()->values();
        $topBuyerUsers = \App\Models\User::whereIn('id', $topBuyerIds)->get()->keyBy('id');
        $topBuyers = $topBuyers->map(function ($b) use ($topBuyerUsers) {
            $user = $topBuyerUsers[$b['user_id']] ?? null;
            return [
                'user_id' => $b['user_id'],
                'name' => $user?->name ?? '-',
                'total_orders' => $b['total_orders'],
                'total_spent' => $b['total_spent'],
            ];
        });

        $topStores = $completedOrders
            ->groupBy('store_id')
            ->map(fn ($orders) => [
                'store_id' => $orders->first()->store_id,
                'total_orders' => $orders->count(),
                'total_revenue' => (int) $orders->sum('total'),
                'total_subtotal' => (int) $orders->sum('subtotal'),
                'total_delivery' => (int) $orders->sum('biaya_antar'),
            ])
            ->sortByDesc('total_revenue')
            ->take(10)
            ->values();

        $topStoreIds = $topStores->pluck('store_id')->filter()->values();
        $topStoreModels = Store::with('alumniProfile.user')
            ->whereIn('id', $topStoreIds)
            ->get()
            ->keyBy('id');
        $topStores = $topStores->map(function ($s) use ($topStoreModels) {
            $store = $topStoreModels[$s['store_id']] ?? null;
            return [
                'store_id' => $s['store_id'],
                'nama_toko' => $store?->name ?? '-',
                'pemilik' => $store?->alumniProfile?->user?->name ?? '-',
                'total_orders' => $s['total_orders'],
                'total_revenue' => $s['total_revenue'],
                'total_subtotal' => $s['total_subtotal'],
                'total_delivery' => $s['total_delivery'],
            ];
        });

        $grafikBulanan = $this->getMonthlyFinance($request);

        return response()->json([
            'total_omzet' => (int) $totalOmzet,
            'total_subtotal' => (int) $totalSubtotal,
            'total_biaya_antar' => (int) $totalBiayaAntar,
            'total_transaksi' => $totalTransaksi,
            'total_selesai' => $totalSelesai,
            'total_dibatalkan' => $totalDibatalkan,
            'rata2_order' => (int) $rata2Order,
            'breakdown_status' => $breakdownStatus,
            'payment_breakdown' => $paymentBreakdown,
            'top_products' => $topProducts,
            'top_buyers' => $topBuyers,
            'top_stores' => $topStores,
            'grafik_bulanan' => $grafikBulanan,
        ]);
    }

    /**
     * Per-store finance list with richer metrics.
     */
    public function perStore(Request $request)
    {
        $query = Order::query();
        $this->applyDateFilter($query, $request);

        $storeStats = (clone $query)
            ->selectRaw('
                store_id,
                count(*) as total_order,
                sum(total) as omzet,
                sum(subtotal) as total_subtotal,
                sum(biaya_antar) as total_biaya_antar,
                avg(total) as rata2_order,
                sum(case when status = \'selesai\' then 1 else 0 end) as selesai,
                sum(case when status = \'dibatalkan\' then 1 else 0 end) as dibatalkan,
                sum(case when status in (\'menunggu_konfirmasi\', \'diproses\') then 1 else 0 end) as aktif
            ')
            ->whereIn('status', ['selesai', 'diproses', 'dalam_pengantaran', 'menunggu_konfirmasi', 'dibatalkan'])
            ->groupBy('store_id')
            ->orderByDesc('omzet')
            ->get();

        $storeIds = $storeStats->pluck('store_id')->filter()->values();
        $stores = Store::with('alumniProfile.user')
            ->whereIn('id', $storeIds)
            ->get()
            ->keyBy('id');

        $data = $storeStats->map(function ($row) use ($stores) {
            $store = $stores[$row->store_id] ?? null;
            return [
                'store_id' => $row->store_id,
                'nama_toko' => $store?->name ?? '-',
                'pemilik' => $store?->alumniProfile?->user?->name ?? '-',
                'kategori_usaha' => $store?->kategori_usaha ?? '-',
                'total_order' => (int) $row->total_order,
                'omzet' => (int) $row->omzet,
                'total_subtotal' => (int) $row->total_subtotal,
                'total_biaya_antar' => (int) $row->total_biaya_antar,
                'rata2_order' => (int) $row->rata2_order,
                'selesai' => (int) $row->selesai,
                'dibatalkan' => (int) $row->dibatalkan,
                'aktif' => (int) $row->aktif,
            ];
        });

        return response()->json($data);
    }

    /**
     * Detailed financial data for a single store.
     */
    public function storeDetail(Request $request, $storeId)
    {
        $store = Store::with('alumniProfile.user', 'deliveryFees')->findOrFail($storeId);

        $query = Order::where('store_id', $storeId);
        $this->applyDateFilter($query, $request);

        $allOrders = (clone $query)->get();
        $completedOrders = (clone $query)->whereIn('status', ['selesai', 'diproses', 'dalam_pengantaran'])->get();

        $totalOmzet = $completedOrders->sum('total');
        $totalSubtotal = $completedOrders->sum('subtotal');
        $totalBiayaAntar = $completedOrders->sum('biaya_antar');
        $totalTransaksi = $allOrders->count();
        $rata2Order = $totalTransaksi > 0 ? round($totalOmzet / $totalTransaksi, 0) : 0;

        $statusBreakdown = $allOrders
            ->groupBy('status')
            ->map(fn ($orders) => [
                'jumlah' => $orders->count(),
                'nominal' => (int) $orders->sum('total'),
            ])
            ->toArray();

        $paymentBreakdown = $completedOrders
            ->groupBy('payment_method')
            ->map(fn ($orders, $method) => [
                'jumlah' => $orders->count(),
                'nominal' => (int) $orders->sum('total'),
            ])
            ->toArray();

        $topProducts = OrderItem::whereIn('order_id', $completedOrders->pluck('id'))
            ->selectRaw('product_id, name, sum(quantity) as total_qty, sum(price * quantity) as total_revenue')
            ->groupBy('product_id', 'name')
            ->orderByDesc('total_revenue')
            ->limit(10)
            ->get();

        $topBuyers = $allOrders
            ->groupBy('user_id')
            ->map(fn ($orders) => [
                'user_id' => $orders->first()->user_id,
                'total_orders' => $orders->count(),
                'total_spent' => (int) $orders->whereIn('status', ['selesai', 'diproses', 'dalam_pengantaran'])->sum('total'),
            ])
            ->sortByDesc('total_spent')
            ->take(5)
            ->values();

        $topBuyerIds = $topBuyers->pluck('user_id')->filter()->values();
        $topBuyerUsers = \App\Models\User::whereIn('id', $topBuyerIds)->get()->keyBy('id');
        $topBuyers = $topBuyers->map(function ($b) use ($topBuyerUsers) {
            $user = $topBuyerUsers[$b['user_id']] ?? null;
            return [
                'user_id' => $b['user_id'],
                'name' => $user?->name ?? '-',
                'total_orders' => $b['total_orders'],
                'total_spent' => $b['total_spent'],
            ];
        });

        $monthlyTrend = $this->getMonthlyFinance($request, $storeId);

        $recentOrders = Order::with('user')
            ->where('store_id', $storeId)
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn ($order) => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'buyer' => $order->user?->name ?? '-',
                'total' => (int) $order->total,
                'status' => $order->status,
                'payment_method' => $order->payment_method,
                'created_at' => $order->created_at->format('d M Y H:i'),
            ]);

        $totalProduk = Product::where('store_id', $storeId)->count();
        $totalJasa = Service::where('store_id', $storeId)->count();

        return response()->json([
            'store' => [
                'id' => $store->id,
                'name' => $store->name,
                'kategori_usaha' => $store->kategori_usaha,
                'pemilik' => $store->alumniProfile?->user?->name ?? '-',
                'status' => $store->status,
                'rating' => $store->average_rating,
                'total_produk' => $totalProduk,
                'total_jasa' => $totalJasa,
                'delivery_type' => $store->delivery_type,
                'fixed_delivery_fee' => (int) $store->fixed_delivery_fee,
                'delivery_fees' => $store->deliveryFees->map(fn ($f) => [
                    'wilayah' => $f->wilayah,
                    'ongkir' => (int) $f->ongkir,
                ]),
            ],
            'total_omzet' => (int) $totalOmzet,
            'total_subtotal' => (int) $totalSubtotal,
            'total_biaya_antar' => (int) $totalBiayaAntar,
            'total_transaksi' => $totalTransaksi,
            'rata2_order' => (int) $rata2Order,
            'status_breakdown' => $statusBreakdown,
            'payment_breakdown' => $paymentBreakdown,
            'top_products' => $topProducts,
            'top_buyers' => $topBuyers,
            'monthly_trend' => $monthlyTrend,
            'recent_orders' => $recentOrders,
        ]);
    }

    private function applyDateFilter($query, Request $request)
    {
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
    }

    private function getMonthlyFinance(Request $request, ?int $storeId = null)
    {
        $months = [];
        for ($i = 11; $i >= 0; $i--) {
            $months[] = now()->subMonths($i)->format('Y-m');
        }

        $stats = [];
        foreach ($months as $month) {
            $year = substr($month, 0, 4);
            $mon = substr($month, 5, 2);

            $q = Order::whereYear('created_at', $year)->whereMonth('created_at', $mon);
            if ($storeId) {
                $q->where('store_id', $storeId);
            }
            $this->applyDateFilter($q, $request);

            $completed = (clone $q)->whereIn('status', ['selesai', 'diproses', 'dalam_pengantaran']);
            $penjualan = $completed->sum('total');
            $subtotal = $completed->sum('subtotal');
            $biayaAntar = $completed->sum('biaya_antar');
            $pesanan = (clone $q)->count();

            $stats[] = [
                'bulan' => $month,
                'penjualan' => (int) $penjualan,
                'subtotal' => (int) $subtotal,
                'biaya_antar' => (int) $biayaAntar,
                'pesanan' => $pesanan,
            ];
        }

        return $stats;
    }
}
