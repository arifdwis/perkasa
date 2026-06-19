<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductImage;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ProductCatalogSeeder extends Seeder
{
    /**
     * Seed 100 demo products across many categories and alumni stores.
     */
    public function run(): void
    {
        $this->call(CategorySeeder::class);

        $stores = $this->ensureDemoStores();
        $categories = ProductCategory::where('is_active', true)->orderBy('name')->get();

        if ($categories->isEmpty()) {
            $this->command?->warn('Tidak ada kategori produk aktif. ProductCatalogSeeder dihentikan.');
            return;
        }

        $productsByCategory = $this->productTemplates();
        $productNumber = 1;

        while ($productNumber <= 100) {
            foreach ($categories as $category) {
                if ($productNumber > 100) {
                    break;
                }

                $templates = $productsByCategory[$category->name] ?? $productsByCategory['UMKM'];
                $template = $templates[($productNumber - 1) % count($templates)];
                $store = $stores[($productNumber - 1) % count($stores)];
                $slug = 'demo-product-' . str_pad((string) $productNumber, 3, '0', STR_PAD_LEFT);

                $product = Product::updateOrCreate(
                    ['slug' => $slug],
                    [
                        'store_id' => $store->id,
                        'product_category_id' => $category->id,
                        'name' => $template['name'] . ' #' . str_pad((string) $productNumber, 3, '0', STR_PAD_LEFT),
                        'description' => $template['description'],
                        'price' => $template['price'],
                        'stock' => $template['stock'],
                        'status' => $template['stock'] > 0 ? 'active' : 'out_of_stock',
                        'is_featured' => $productNumber <= 18 || $productNumber % 10 === 0,
                    ]
                );

                ProductImage::updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'is_primary' => true,
                    ],
                    [
                        'image_path' => 'https://picsum.photos/seed/perkasa-' . $slug . '/700/700',
                    ]
                );

                $productNumber++;
            }
        }

        $this->command?->info('100 produk demo lintas kategori berhasil dibuat/diperbarui.');
    }

    private function ensureDemoStores()
    {
        $sellerStores = [
            [
                'name' => 'Nabila Rasa Samarinda',
                'email' => 'catalog.seller1@perkasa.test',
                'nim' => '1801026001',
                'program_studi' => 'S1 Manajemen',
                'store' => 'Nabila Rasa Samarinda',
                'kategori_usaha' => 'Makanan dan Minuman',
                'description' => 'Toko alumni FEB untuk makanan rumahan, minuman segar, dan camilan khas Samarinda.',
            ],
            [
                'name' => 'Arman Style Studio',
                'email' => 'catalog.seller2@perkasa.test',
                'nim' => '1801026002',
                'program_studi' => 'S1 Akuntansi',
                'store' => 'Arman Style Studio',
                'kategori_usaha' => 'Fashion',
                'description' => 'Pilihan fashion harian, batik kerja, hijab, totebag, dan aksesori produksi alumni.',
            ],
            [
                'name' => 'Dewi Tech Corner',
                'email' => 'catalog.seller3@perkasa.test',
                'nim' => '1801026003',
                'program_studi' => 'S1 Ekonomi Pembangunan',
                'store' => 'Dewi Tech Corner',
                'kategori_usaha' => 'Elektronik',
                'description' => 'Aksesori gadget, alat kerja digital, dan perlengkapan produktivitas untuk alumni.',
            ],
            [
                'name' => 'Ridho Buku & Craft',
                'email' => 'catalog.seller4@perkasa.test',
                'nim' => '1801026004',
                'program_studi' => 'S1 Manajemen',
                'store' => 'Ridho Buku & Craft',
                'kategori_usaha' => 'Buku',
                'description' => 'Buku kuliah, alat tulis, kerajinan tangan, dan hadiah wisuda buatan alumni.',
            ],
            [
                'name' => 'Maya Agro UMKM',
                'email' => 'catalog.seller5@perkasa.test',
                'nim' => '1801026005',
                'program_studi' => 'S1 Akuntansi',
                'store' => 'Maya Agro UMKM',
                'kategori_usaha' => 'UMKM',
                'description' => 'Produk pertanian, olahan lokal, perlengkapan otomotif ringan, dan paket UMKM Kaltim.',
            ],
        ];

        return collect($sellerStores)->map(function (array $data, int $index) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('password123'),
                ]
            );

            if (method_exists($user, 'assignRole') && ! $user->hasRole('alumni_penjual')) {
                $user->assignRole('alumni_penjual');
            }

            $profile = $user->profile()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'nim' => $data['nim'],
                    'program_studi' => $data['program_studi'],
                    'tahun_masuk' => 2018 + ($index % 4),
                    'tahun_lulus' => 2022 + ($index % 3),
                    'whatsapp' => '62812555010' . $index,
                    'domisili' => 'Samarinda',
                    'status_verifikasi' => 'verified',
                    'badge_verified' => true,
                ]
            );

            return Store::updateOrCreate(
                ['alumni_profile_id' => $profile->id],
                [
                    'name' => $data['store'],
                    'description' => $data['description'],
                    'kategori_usaha' => $data['kategori_usaha'],
                    'whatsapp' => '62812555020' . $index,
                    'kota' => 'Samarinda',
                    'tahun_berdiri' => 2024,
                    'status' => 'active',
                    'delivery_type' => $index % 2 === 0 ? 'fixed' : 'per_wilayah',
                    'fixed_delivery_fee' => 8000 + ($index * 1000),
                    'logo' => 'https://picsum.photos/seed/perkasa-store-logo-' . $index . '/300/300',
                    'banner' => 'https://picsum.photos/seed/perkasa-store-banner-' . $index . '/900/360',
                ]
            );
        })->values();
    }

    private function productTemplates(): array
    {
        return [
            'Makanan dan Minuman' => [
                ['name' => 'Kopi Susu Aren Alumni', 'price' => 18000, 'stock' => 35, 'description' => 'Kopi susu gula aren dengan espresso robusta Samarinda, cocok untuk teman kerja dan kuliah.'],
                ['name' => 'Dimsum Ayam Homemade', 'price' => 28000, 'stock' => 24, 'description' => 'Dimsum ayam isi padat lengkap dengan saus pedas manis racikan rumahan.'],
                ['name' => 'Brownies Panggang Sekat', 'price' => 65000, 'stock' => 15, 'description' => 'Brownies panggang sekat dengan topping cokelat, keju, almond, dan oreo.'],
                ['name' => 'Amplang Tenggiri Kaltim', 'price' => 32000, 'stock' => 40, 'description' => 'Camilan amplang ikan tenggiri gurih khas Kalimantan Timur untuk oleh-oleh.'],
                ['name' => 'Salad Buah Keju Premium', 'price' => 25000, 'stock' => 18, 'description' => 'Salad buah segar dengan dressing yogurt mayo dan parutan keju melimpah.'],
            ],
            'Fashion' => [
                ['name' => 'Kaos Alumni FEB Unmul', 'price' => 95000, 'stock' => 30, 'description' => 'Kaos cotton combed nyaman dengan desain identitas alumni FEB Unmul.'],
                ['name' => 'Hoodie Fleece Deep Green', 'price' => 225000, 'stock' => 12, 'description' => 'Hoodie fleece tebal warna hijau deep green, hangat dan cocok untuk kegiatan kampus.'],
                ['name' => 'Batik Motif Kaltim Formal', 'price' => 180000, 'stock' => 16, 'description' => 'Kemeja batik motif Kaltim berbahan katun untuk kerja, seminar, dan acara alumni.'],
                ['name' => 'Hijab Voal Laser Cut', 'price' => 55000, 'stock' => 45, 'description' => 'Hijab voal ringan dengan tepi laser cut rapi dan pilihan warna profesional.'],
                ['name' => 'Totebag Canvas FEB', 'price' => 42000, 'stock' => 50, 'description' => 'Totebag canvas tebal untuk laptop kecil, buku, dan aktivitas harian.'],
            ],
            'Elektronik' => [
                ['name' => 'Powerbank Slim 10000mAh', 'price' => 135000, 'stock' => 20, 'description' => 'Powerbank tipis dual output untuk kebutuhan kerja mobile dan perjalanan.'],
                ['name' => 'Mouse Wireless Silent', 'price' => 75000, 'stock' => 28, 'description' => 'Mouse wireless silent click dengan desain ergonomis untuk kerja kantor.'],
                ['name' => 'TWS Bluetooth Mini Bass', 'price' => 185000, 'stock' => 14, 'description' => 'Earphone TWS compact dengan suara jernih dan charging case praktis.'],
                ['name' => 'Stand Laptop Aluminium', 'price' => 115000, 'stock' => 22, 'description' => 'Stand laptop lipat aluminium untuk posisi kerja lebih ergonomis.'],
                ['name' => 'Lampu Meja LED Rechargeable', 'price' => 69000, 'stock' => 25, 'description' => 'Lampu belajar LED portable dengan tiga tingkat cahaya dan baterai isi ulang.'],
            ],
            'Buku' => [
                ['name' => 'Pengantar Akuntansi Praktis', 'price' => 98000, 'stock' => 19, 'description' => 'Buku ringkas konsep akuntansi dasar dengan contoh soal dan pembahasan.'],
                ['name' => 'Manajemen Keuangan UMKM', 'price' => 87000, 'stock' => 17, 'description' => 'Panduan mengelola kas, modal kerja, dan laporan sederhana untuk pelaku UMKM.'],
                ['name' => 'Binder Catatan Kuliah A5', 'price' => 52000, 'stock' => 33, 'description' => 'Binder A5 dengan cover elegan dan isi kertas bergaris untuk catatan kuliah.'],
                ['name' => 'Paket Stabilo Pastel', 'price' => 24000, 'stock' => 42, 'description' => 'Set highlighter warna pastel untuk menandai materi kuliah dan dokumen kerja.'],
            ],
            'Kerajinan' => [
                ['name' => 'Buket Flanel Wisuda', 'price' => 85000, 'stock' => 10, 'description' => 'Buket bunga flanel handmade untuk hadiah wisuda dan acara alumni.'],
                ['name' => 'Lilin Aromaterapi Lavender', 'price' => 35000, 'stock' => 26, 'description' => 'Lilin aromaterapi wangi lavender untuk suasana ruang kerja lebih tenang.'],
                ['name' => 'Gantungan Kunci Rajut', 'price' => 18000, 'stock' => 55, 'description' => 'Gantungan kunci rajut karakter lucu, cocok untuk souvenir komunitas.'],
                ['name' => 'Hiasan Meja Resin Custom', 'price' => 75000, 'stock' => 9, 'description' => 'Hiasan meja resin custom dengan pilihan warna dan tulisan singkat.'],
            ],
            'Properti' => [
                ['name' => 'Tanaman Meja Ruang Kerja', 'price' => 45000, 'stock' => 21, 'description' => 'Tanaman hias mini dalam pot keramik untuk meja kerja dan ruang tamu.'],
                ['name' => 'Rak Dinding Minimalis', 'price' => 125000, 'stock' => 8, 'description' => 'Rak dinding kayu minimalis untuk dekorasi kamar kos, kantor, atau rumah.'],
                ['name' => 'Lampu Tidur Kayu Natural', 'price' => 95000, 'stock' => 11, 'description' => 'Lampu tidur bernuansa kayu dengan cahaya hangat untuk dekorasi interior.'],
            ],
            'Otomotif' => [
                ['name' => 'Parfum Mobil Kopi', 'price' => 28000, 'stock' => 36, 'description' => 'Parfum mobil aroma kopi yang elegan dan tidak menyengat.'],
                ['name' => 'Kanebo Microfiber Premium', 'price' => 26000, 'stock' => 44, 'description' => 'Lap microfiber lembut untuk membersihkan motor, mobil, dan helm.'],
                ['name' => 'Cover Motor Anti Air', 'price' => 110000, 'stock' => 13, 'description' => 'Cover motor bahan parasut anti air untuk melindungi kendaraan dari hujan dan debu.'],
            ],
            'Pertanian' => [
                ['name' => 'Pupuk Kompos Organik', 'price' => 18000, 'stock' => 60, 'description' => 'Pupuk kompos organik siap pakai untuk tanaman hias, cabai, dan sayuran rumah.'],
                ['name' => 'Benih Cabai Rawit Unggul', 'price' => 15000, 'stock' => 48, 'description' => 'Benih cabai rawit dengan daya tumbuh tinggi untuk kebun kecil.'],
                ['name' => 'Media Tanam Siap Pakai', 'price' => 22000, 'stock' => 37, 'description' => 'Campuran tanah, sekam, dan kompos untuk tanaman pot rumah.'],
            ],
            'UMKM' => [
                ['name' => 'Madu Hutan Kaltim 250ml', 'price' => 85000, 'stock' => 24, 'description' => 'Madu hutan murni Kalimantan Timur dalam botol 250ml.'],
                ['name' => 'Keripik Pisang Cokelat', 'price' => 27000, 'stock' => 38, 'description' => 'Keripik pisang renyah dengan baluran cokelat manis, cocok untuk camilan kantor.'],
                ['name' => 'Sambal Bawang Botol', 'price' => 32000, 'stock' => 32, 'description' => 'Sambal bawang pedas gurih dalam botol, dibuat dari bahan segar pilihan.'],
                ['name' => 'Paket Hampers UMKM Alumni', 'price' => 150000, 'stock' => 12, 'description' => 'Paket hampers berisi produk pilihan UMKM alumni untuk hadiah dan acara kantor.'],
            ],
        ];
    }
}
