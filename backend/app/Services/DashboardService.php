<?php

namespace App\Services;

use App\Models\AlumniProfile;
use App\Models\Order;
use App\Models\Product;
use App\Models\Service;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function getAdminStats()
    {
        $totalAlumni = User::count();
        $alumniTerverifikasi = AlumniProfile::where('status_verifikasi', 'verified')->count();
        $totalToko = Store::count();
        $totalProduk = Product::count();
        $totalJasa = Service::count();
        $totalPesanan = Order::count();
        $totalTransaksiCod = Order::where('payment_method', 'cod')
            ->whereIn('status', ['selesai', 'diproses', 'dalam_pengantaran'])
            ->sum('total');

        $grafikBulanan = $this->getMonthlyStats();
        $tokoTerlaris = $this->getTopStores();
        $alumniTeraktif = $this->getMostActiveAlumni();

        return [
            'total_alumni' => $totalAlumni,
            'alumni_terverifikasi' => $alumniTerverifikasi,
            'total_toko' => $totalToko,
            'total_produk' => $totalProduk,
            'total_jasa' => $totalJasa,
            'total_pesanan' => $totalPesanan,
            'total_transaksi_cod' => $totalTransaksiCod,
            'grafik_bulanan' => $grafikBulanan,
            'toko_terlaris' => $tokoTerlaris,
            'alumni_teraktif' => $alumniTeraktif,
        ];
    }

    public function getSellerStats($storeId)
    {
        $store = Store::find($storeId);

        if (! $store) {
            return null;
        }

        $totalProduk = Product::where('store_id', $storeId)->count();
        $totalJasa = Service::where('store_id', $storeId)->count();
        $totalPesanan = Order::where('store_id', $storeId)->count();
        $totalPenjualan = Order::where('store_id', $storeId)
            ->whereIn('status', ['selesai', 'diproses', 'dalam_pengantaran'])
            ->sum('total');
        $ratingToko = $store->average_rating;

        $grafikBulanan = $this->getSellerMonthlyStats($storeId);
        $produkTerlaris = $this->getTopProducts($storeId);

        return [
            'total_produk' => $totalProduk,
            'total_jasa' => $totalJasa,
            'total_pesanan' => $totalPesanan,
            'total_penjualan' => $totalPenjualan,
            'rating_toko' => $ratingToko,
            'grafik_bulanan' => $grafikBulanan,
            'produk_terlaris' => $produkTerlaris,
        ];
    }

    public function getBuyerStats($userId)
    {
        $pesananAktif = Order::where('user_id', $userId)
            ->whereIn('status', ['menunggu_konfirmasi', 'diproses', 'dalam_pengantaran'])
            ->count();

        $riwayatPesanan = Order::where('user_id', $userId)
            ->whereIn('status', ['selesai', 'dibatalkan'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $totalFavorit = DB::table('favorites')
            ->where('user_id', $userId)
            ->count();

        $ulasanSaya = DB::table('reviews')
            ->where('user_id', $userId)
            ->count();

        $totalBelanja = Order::where('user_id', $userId)
            ->where('status', 'selesai')
            ->sum('total');

        return [
            'pesanan_aktif' => $pesananAktif,
            'riwayat_pesanan' => $riwayatPesanan,
            'total_favorit' => $totalFavorit,
            'ulasan_saya' => $ulasanSaya,
            'total_belanja' => $totalBelanja,
        ];
    }

    private function getMonthlyStats()
    {
        $months = [];
        for ($i = 11; $i >= 0; $i--) {
            $months[] = now()->subMonths($i)->format('Y-m');
        }

        $stats = [];
        foreach ($months as $month) {
            $stats[] = [
                'bulan' => $month,
                'pesanan' => Order::whereYear('created_at', substr($month, 0, 4))
                    ->whereMonth('created_at', substr($month, 5, 2))
                    ->count(),
                'penjualan' => Order::whereYear('created_at', substr($month, 0, 4))
                    ->whereMonth('created_at', substr($month, 5, 2))
                    ->sum('total'),
            ];
        }

        return $stats;
    }

    private function getSellerMonthlyStats($storeId)
    {
        $months = [];
        for ($i = 11; $i >= 0; $i--) {
            $months[] = now()->subMonths($i)->format('Y-m');
        }

        $stats = [];
        foreach ($months as $month) {
            $stats[] = [
                'bulan' => $month,
                'pesanan' => Order::where('store_id', $storeId)
                    ->whereYear('created_at', substr($month, 0, 4))
                    ->whereMonth('created_at', substr($month, 5, 2))
                    ->count(),
                'penjualan' => Order::where('store_id', $storeId)
                    ->whereYear('created_at', substr($month, 0, 4))
                    ->whereMonth('created_at', substr($month, 5, 2))
                    ->sum('total'),
            ];
        }

        return $stats;
    }

    private function getTopStores()
    {
        return Store::with(['alumniProfile.user'])
            ->withCount('orders')
            ->orderBy('orders_count', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($store) {
                return [
                    'id' => $store->id,
                    'name' => $store->name,
                    'orders_count' => $store->orders_count,
                    'owner' => $store->alumniProfile->user->name ?? '',
                ];
            });
    }

    private function getMostActiveAlumni()
    {
        return User::with(['profile', 'orders'])
            ->withCount('orders as total_orders')
            ->orderBy('total_orders', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'total_orders' => $user->total_orders,
                    'program_studi' => $user->profile->program_studi ?? '-',
                ];
            });
    }

    private function getTopProducts($storeId)
    {
        return Product::where('store_id', $storeId)
            ->withCount(['orderItems as total_sold' => function ($query) {
                $query->select(DB::raw('sum(quantity)'));
            }])
            ->orderBy('total_sold', 'desc')
            ->limit(5)
            ->get();
    }
}
