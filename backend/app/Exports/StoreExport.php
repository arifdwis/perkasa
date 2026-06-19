<?php

namespace App\Exports;

use App\Models\Store;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StoreExport implements FromView, ShouldAutoSize, WithHeadings
{
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    public function view(): View
    {
        $query = Store::with(['alumniProfile.user']);

        if (isset($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (isset($this->filters['kota'])) {
            $query->where('kota', $this->filters['kota']);
        }

        $stores = $query->orderBy('created_at', 'desc')->get();

        return view('exports.stores', [
            'stores' => $stores,
        ]);
    }

    public function headings(): array
    {
        return [
            'No',
            'Nama Toko',
            'Pemilik',
            'Email',
            'Program Studi',
            'Kota',
            'Kategori Usaha',
            'WhatsApp',
            'Status',
            'Dibuat',
        ];
    }
}
