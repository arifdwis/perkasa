<?php

namespace Database\Seeders;

use App\Models\ProductCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $productCategories = [
            'Makanan dan Minuman',
            'Fashion',
            'Elektronik',
            'Buku',
            'Kerajinan',
            'Properti',
            'Otomotif',
            'Pertanian',
            'UMKM',
        ];

        foreach ($productCategories as $name) {
            ProductCategory::updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'is_active' => true,
                ]
            );
        }
    }
}
