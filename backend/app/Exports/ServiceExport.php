<?php

namespace App\Exports;

use App\Models\Service;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ServiceExport implements FromView, ShouldAutoSize, WithHeadings
{
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    public function view(): View
    {
        $query = Service::with(['store.alumniProfile', 'category']);

        if (isset($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (isset($this->filters['store_id'])) {
            $query->where('store_id', $this->filters['store_id']);
        }

        if (isset($this->filters['service_category_id'])) {
            $query->where('service_category_id', $this->filters['service_category_id']);
        }

        $services = $query->orderBy('created_at', 'desc')->get();

        return view('exports.services', [
            'services' => $services,
        ]);
    }

    public function headings(): array
    {
        return [
            'No',
            'Nama Jasa',
            'Toko',
            'Pemilik',
            'Kategori',
            'Harga Mulai Dari',
            'Lokasi Layanan',
            'Status',
            'Unggulan',
            'Dibuat',
        ];
    }
}
