<?php

namespace App\Http\Controllers\Api;

use App\Exports\AlumniExport;
use App\Exports\OrderExport;
use App\Exports\ProductExport;
use App\Exports\SalesExport;
use App\Exports\ServiceExport;
use App\Exports\StoreExport;
use App\Http\Controllers\Controller;
use App\Models\AlumniProfile;
use App\Models\Order;
use App\Models\Product;
use App\Models\Service;
use App\Models\Store;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    /**
     * Export Alumni Report.
     */
    public function exportAlumni(Request $request)
    {
        $format = $request->query('format', 'excel');
        $filters = $request->only(['status_verifikasi', 'program_studi', 'tahun_masuk', 'tahun_lulus']);

        if ($format === 'pdf') {
            $query = AlumniProfile::with('user');

            if (! empty($filters['status_verifikasi'])) {
                $query->where('status_verifikasi', $filters['status_verifikasi']);
            }
            if (! empty($filters['program_studi'])) {
                $query->where('program_studi', $filters['program_studi']);
            }
            if (! empty($filters['tahun_masuk'])) {
                $query->where('tahun_masuk', $filters['tahun_masuk']);
            }
            if (! empty($filters['tahun_lulus'])) {
                $query->where('tahun_lulus', $filters['tahun_lulus']);
            }

            $alumni = $query->orderBy('created_at', 'desc')->get();
            $pdf = Pdf::loadView('exports.alumni', [
                'alumni' => $alumni,
                'is_pdf' => true,
            ])->setPaper('a4', 'landscape');

            return $pdf->download('laporan_alumni_'.now()->format('YmdHis').'.pdf');
        }

        $export = new AlumniExport($filters);
        $fileName = 'laporan_alumni_'.now()->format('YmdHis');

        if ($format === 'csv') {
            return Excel::download($export, $fileName.'.csv', \Maatwebsite\Excel\Excel::CSV);
        }

        return Excel::download($export, $fileName.'.xlsx', \Maatwebsite\Excel\Excel::XLSX);
    }

    /**
     * Export Store Report.
     */
    public function exportStores(Request $request)
    {
        $format = $request->query('format', 'excel');
        $filters = $request->only(['status', 'kota']);

        if ($format === 'pdf') {
            $query = Store::with(['alumniProfile.user']);

            if (! empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }
            if (! empty($filters['kota'])) {
                $query->where('kota', $filters['kota']);
            }

            $stores = $query->orderBy('created_at', 'desc')->get();
            $pdf = Pdf::loadView('exports.stores', [
                'stores' => $stores,
                'is_pdf' => true,
            ])->setPaper('a4', 'landscape');

            return $pdf->download('laporan_toko_'.now()->format('YmdHis').'.pdf');
        }

        $export = new StoreExport($filters);
        $fileName = 'laporan_toko_'.now()->format('YmdHis');

        if ($format === 'csv') {
            return Excel::download($export, $fileName.'.csv', \Maatwebsite\Excel\Excel::CSV);
        }

        return Excel::download($export, $fileName.'.xlsx', \Maatwebsite\Excel\Excel::XLSX);
    }

    /**
     * Export Product Report.
     */
    public function exportProducts(Request $request)
    {
        $format = $request->query('format', 'excel');
        $filters = $request->only(['status', 'store_id', 'product_category_id']);

        if ($format === 'pdf') {
            $query = Product::with(['store.alumniProfile', 'category']);

            if (! empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }
            if (! empty($filters['store_id'])) {
                $query->where('store_id', $filters['store_id']);
            }
            if (! empty($filters['product_category_id'])) {
                $query->where('product_category_id', $filters['product_category_id']);
            }

            $products = $query->orderBy('created_at', 'desc')->get();
            $pdf = Pdf::loadView('exports.products', [
                'products' => $products,
                'is_pdf' => true,
            ])->setPaper('a4', 'landscape');

            return $pdf->download('laporan_produk_'.now()->format('YmdHis').'.pdf');
        }

        $export = new ProductExport($filters);
        $fileName = 'laporan_produk_'.now()->format('YmdHis');

        if ($format === 'csv') {
            return Excel::download($export, $fileName.'.csv', \Maatwebsite\Excel\Excel::CSV);
        }

        return Excel::download($export, $fileName.'.xlsx', \Maatwebsite\Excel\Excel::XLSX);
    }

    /**
     * Export Service Report.
     */
    public function exportServices(Request $request)
    {
        $format = $request->query('format', 'excel');
        $filters = $request->only(['status', 'store_id', 'service_category_id']);

        if ($format === 'pdf') {
            $query = Service::with(['store.alumniProfile', 'category']);

            if (! empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }
            if (! empty($filters['store_id'])) {
                $query->where('store_id', $filters['store_id']);
            }
            if (! empty($filters['service_category_id'])) {
                $query->where('service_category_id', $filters['service_category_id']);
            }

            $services = $query->orderBy('created_at', 'desc')->get();
            $pdf = Pdf::loadView('exports.services', [
                'services' => $services,
                'is_pdf' => true,
            ])->setPaper('a4', 'landscape');

            return $pdf->download('laporan_jasa_'.now()->format('YmdHis').'.pdf');
        }

        $export = new ServiceExport($filters);
        $fileName = 'laporan_jasa_'.now()->format('YmdHis');

        if ($format === 'csv') {
            return Excel::download($export, $fileName.'.csv', \Maatwebsite\Excel\Excel::CSV);
        }

        return Excel::download($export, $fileName.'.xlsx', \Maatwebsite\Excel\Excel::XLSX);
    }

    /**
     * Export Order Report.
     */
    public function exportOrders(Request $request)
    {
        $format = $request->query('format', 'excel');
        $filters = $request->only(['status', 'store_id', 'user_id', 'date_from', 'date_to']);

        if ($format === 'pdf') {
            $query = Order::with(['user.profile', 'store.alumniProfile', 'items']);

            if (! empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }
            if (! empty($filters['store_id'])) {
                $query->where('store_id', $filters['store_id']);
            }
            if (! empty($filters['user_id'])) {
                $query->where('user_id', $filters['user_id']);
            }
            if (! empty($filters['date_from'])) {
                $query->whereDate('created_at', '>=', $filters['date_from']);
            }
            if (! empty($filters['date_to'])) {
                $query->whereDate('created_at', '<=', $filters['date_to']);
            }

            $orders = $query->orderBy('created_at', 'desc')->get();
            $pdf = Pdf::loadView('exports.orders', [
                'orders' => $orders,
                'is_pdf' => true,
            ])->setPaper('a4', 'landscape');

            return $pdf->download('laporan_pesanan_'.now()->format('YmdHis').'.pdf');
        }

        $export = new OrderExport($filters);
        $fileName = 'laporan_pesanan_'.now()->format('YmdHis');

        if ($format === 'csv') {
            return Excel::download($export, $fileName.'.csv', \Maatwebsite\Excel\Excel::CSV);
        }

        return Excel::download($export, $fileName.'.xlsx', \Maatwebsite\Excel\Excel::XLSX);
    }

    /**
     * Export Sales / Penjualan Report.
     */
    public function exportSales(Request $request)
    {
        $format = $request->query('format', 'excel');
        $filters = $request->only(['store_id', 'date_from', 'date_to']);

        if ($format === 'pdf') {
            $query = Order::with(['user.profile', 'store.alumniProfile', 'items']);

            if (! empty($filters['store_id'])) {
                $query->where('store_id', $filters['store_id']);
            }
            if (! empty($filters['date_from'])) {
                $query->whereDate('created_at', '>=', $filters['date_from']);
            }
            if (! empty($filters['date_to'])) {
                $query->whereDate('created_at', '<=', $filters['date_to']);
            }

            $query->whereIn('status', ['selesai', 'diproses', 'dalam_pengantaran']);

            $sales = $query->orderBy('created_at', 'desc')->get();
            $pdf = Pdf::loadView('exports.sales', [
                'sales' => $sales,
                'is_pdf' => true,
            ])->setPaper('a4', 'landscape');

            return $pdf->download('laporan_penjualan_'.now()->format('YmdHis').'.pdf');
        }

        $export = new SalesExport($filters);
        $fileName = 'laporan_penjualan_'.now()->format('YmdHis');

        if ($format === 'csv') {
            return Excel::download($export, $fileName.'.csv', \Maatwebsite\Excel\Excel::CSV);
        }

        return Excel::download($export, $fileName.'.xlsx', \Maatwebsite\Excel\Excel::XLSX);
    }
}
