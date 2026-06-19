<?php

namespace App\Exports;

use App\Models\Product;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProductExport implements FromView, ShouldAutoSize, WithHeadings
{
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    public function view(): View
    {
        $query = Product::with(['store.alumniProfile', 'category']);

        if (isset($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (isset($this->filters['store_id'])) {
            $query->where('store_id', $this->filters['store_id']);
        }

        if (isset($this->filters['product_category_id'])) {
            $query->where('product_category_id', $this->filters['product_category_id']);
        }

        $products = $query->orderBy('created_at', 'desc')->get();

        return view('exports.products', [
            'products' => $products,
        ]);
    }

    public function headings(): array
    {
        return [
            'No',
            'Nama Produk',
            'Toko',
            'Pemilik',
            'Kategori',
            'Harga',
            'Stok',
            'Status',
            'Unggulan',
            'Dibuat',
        ];
    }
}
