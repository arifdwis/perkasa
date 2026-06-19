<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Store;
use Illuminate\Http\Request;

class AdminFinanceController extends Controller
{
    public function summary(Request $request)
    {
        $query = Order::query();

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $completedQuery = (clone $query)->whereIn('status', ['selesai', 'diproses', 'dalam_pengantaran']);

        $totalOmzet = $completedQuery->clone()->sum('total');
        $totalTransaksi = (clone $query)->count();
        $rata2Order = $totalTransaksi > 0 ? round($totalOmzet / max($totalTransaksi, 1), 0) : 0;

        $breakdownStatus = (clone $query)
            ->selectRaw('status, count(*) as jumlah, sum(total) as nominal')
            ->groupBy('status')
            ->get()
            ->pluck('jumlah', 'status');

        $grafikBulanan = $this->getMonthlyFinance($request);

        return response()->json([
            'total_omzet' => (int) $totalOmzet,
            'total_transaksi' => $totalTransaksi,
            'rata2_order' => (int) $rata2Order,
            'breakdown_status' => $breakdownStatus,
            'grafik_bulanan' => $grafikBulanan,
        ]);
    }

    public function perStore(Request $request)
    {
        $query = Order::query()
            ->selectRaw('store_id, count(*) as total_order, sum(total) as omzet')
            ->whereIn('status', ['selesai', 'diproses', 'dalam_pengantaran'])
            ->groupBy('store_id')
            ->orderByDesc('omzet');

        $results = $query->get();

        $storeIds = $results->pluck('store_id')->filter()->values();
        $stores = Store::with('alumniProfile.user')
            ->whereIn('id', $storeIds)
            ->get()
            ->keyBy('id');

        $data = $results->map(function ($row) use ($stores) {
            $store = $stores[$row->store_id] ?? null;
            return [
                'store_id' => $row->store_id,
                'nama_toko' => $store?->name ?? '-',
                'pemilik' => $store?->alumniProfile?->user?->name ?? '-',
                'total_order' => (int) $row->total_order,
                'omzet' => (int) $row->omzet,
            ];
        });

        return response()->json($data);
    }

    private function getMonthlyFinance(Request $request)
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
            if ($request->filled('date_from')) {
                $q->whereDate('created_at', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $q->whereDate('created_at', '<=', $request->date_to);
            }

            $penjualan = (clone $q)->whereIn('status', ['selesai', 'diproses', 'dalam_pengantaran'])->sum('total');
            $pesanan = (clone $q)->count();

            $stats[] = [
                'bulan' => $month,
                'penjualan' => (int) $penjualan,
                'pesanan' => $pesanan,
            ];
        }

        return $stats;
    }
}
