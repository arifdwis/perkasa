<?php

namespace Database\Seeders;

use App\Models\ProductCategory;
use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Seed Product Categories
        $productCategories = [
            'Makanan dan Minuman',
            'Fashion',
            'Elektronik',
            'Buku',
            'Kerajinan',
            'Properti',
            'Otomotif',
            'Pertanian',
            'UMKM'
        ];

        foreach ($productCategories as $name) {
            ProductCategory::updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'is_active' => true
                ]
            );
        }

        // 2. Seed Service Categories
        $serviceCategories = [
            'Konsultan',
            'Akuntan',
            'Auditor',
            'Pajak',
            'Trainer',
            'Fotografer',
            'Videografer',
            'Programmer',
            'Desain Grafis',
            'Digital Marketing',
            'Notaris',
            'Pengacara'
        ];

        foreach ($serviceCategories as $name) {
            ServiceCategory::updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'is_active' => true
                ]
            );
        }
    }
}
