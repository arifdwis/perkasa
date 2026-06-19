<?php

namespace App\Exports;

use App\Models\Order;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class OrderExport implements FromView, ShouldAutoSize, WithHeadings
{
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    public function view(): View
    {
        $query = Order::with(['user.profile', 'store.alumniProfile', 'items']);

        if (isset($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (isset($this->filters['store_id'])) {
            $query->where('store_id', $this->filters['store_id']);
        }

        if (isset($this->filters['user_id'])) {
            $query->where('user_id', $this->filters['user_id']);
        }

        if (isset($this->filters['date_from'])) {
            $query->whereDate('created_at', '>=', $this->filters['date_from']);
        }

        if (isset($this->filters['date_to'])) {
            $query->whereDate('created_at', '<=', $this->filters['date_to']);
        }

        $orders = $query->orderBy('created_at', 'desc')->get();

        return view('exports.orders', [
            'orders' => $orders,
        ]);
    }

    public function headings(): array
    {
        return [
            'No',
            'Nomor Order',
            'Pembeli',
            'Toko',
            'Total',
            'Status',
            'Metode Pembayaran',
            'Tanggal',
        ];
    }
}
