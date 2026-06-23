<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Store;
use App\Models\Product;
use App\Models\Service;
use App\Models\ProductCategory;
use App\Models\ServiceCategory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Review;
use App\Models\OrderStatusLog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Ensure categories are seeded
        $this->command->info('Seeding categories...');
        $this->call(CategorySeeder::class);

        // 2. Fetch or create default users from DatabaseSeeder (for safety)
        $admin = User::where('email', 'admin@perkasa.test')->first();
        if (!$admin) {
            $admin = User::create([
                'name' => 'Super Admin FEB',
                'email' => 'admin@perkasa.test',
                'password' => bcrypt('password123'),
            ]);
            $admin->assignRole('super_admin');
        }

        // 3. Create 3 Buyers (verified)
        $this->command->info('Creating 3 Buyers...');
        $buyers = [];
        $buyerData = [
            ['name' => 'Budi Hermawan', 'email' => 'buyer1@perkasa.test', 'nim' => '1801015011'],
            ['name' => 'Siti Rahma', 'email' => 'buyer2@perkasa.test', 'nim' => '1801015012'],
            ['name' => 'Dewi Lestari', 'email' => 'buyer3@perkasa.test', 'nim' => '1801015013'],
        ];

        foreach ($buyerData as $index => $b) {
            $user = User::updateOrCreate(
                ['email' => $b['email']],
                ['name' => $b['name'], 'password' => Hash::make('password123')]
            );
            $user->assignRole('alumni_pembeli');
            $user->profile()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'nim' => $b['nim'],
                    'program_studi' => 'S1 Manajemen',
                    'tahun_masuk' => 2018,
                    'tahun_lulus' => 2022,
                    'whatsapp' => '08123456780' . ($index + 1),
                    'status_verifikasi' => 'verified',
                    'badge_verified' => true,
                ]
            );
            $buyers[] = $user;
        }

        // 4. Create 3 Sellers (verified) with 3 Active Stores
        $this->command->info('Creating 3 Sellers & Stores...');
        $sellers = [];
        $stores = [];
        $sellerData = [
            [
                'name' => 'Hendra Wijaya',
                'email' => 'seller1@perkasa.test',
                'nim' => '1801015021',
                'store' => [
                    'name' => 'Hendra Coffee & Kitchen',
                    'description' => 'Menyediakan makanan berat, cemilan, dan kopi segar berkualitas buatan alumni FEB.',
                    'kategori_usaha' => 'Makanan dan Minuman',
                    'delivery_type' => 'fixed',
                    'fixed_delivery_fee' => 5000,
                ]
            ],
            [
                'name' => 'Rina Amelia',
                'email' => 'seller2@perkasa.test',
                'nim' => '1801015022',
                'store' => [
                    'name' => 'Rina Boutique & Craft',
                    'description' => 'Busana rajutan, hijab voal, totebag kanvas, dan aksesoris kerajinan tangan estetik.',
                    'kategori_usaha' => 'Fashion',
                    'delivery_type' => 'per_wilayah',
                    'fixed_delivery_fee' => 0,
                ]
            ],
            [
                'name' => 'Fajar Pratama',
                'email' => 'seller3@perkasa.test',
                'nim' => '1801015023',
                'store' => [
                    'name' => 'Pratama Tech & Books',
                    'description' => 'Pusat aksesoris gadget, buku diktat perkuliahan, alat tulis, dan layanan cetak dokumen.',
                    'kategori_usaha' => 'Elektronik',
                    'delivery_type' => 'fixed',
                    'fixed_delivery_fee' => 4000,
                ]
            ],
        ];

        foreach ($sellerData as $index => $s) {
            $user = User::updateOrCreate(
                ['email' => $s['email']],
                ['name' => $s['name'], 'password' => Hash::make('password123')]
            );
            $user->assignRole('alumni_penjual');
            
            $profile = $user->profile()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'nim' => $s['nim'],
                    'program_studi' => 'S1 Akuntansi',
                    'tahun_masuk' => 2018,
                    'tahun_lulus' => 2022,
                    'whatsapp' => '08123456790' . ($index + 1),
                    'status_verifikasi' => 'verified',
                    'badge_verified' => true,
                ]
            );
            
            $store = Store::updateOrCreate(
                ['alumni_profile_id' => $profile->id],
                [
                    'name' => $s['store']['name'],
                    'description' => $s['store']['description'],
                    'kategori_usaha' => $s['store']['kategori_usaha'],
                    'whatsapp' => '628123456790' . ($index + 1),
                    'kota' => 'Samarinda',
                    'tahun_berdiri' => 2024,
                    'status' => 'active',
                    'delivery_type' => $s['store']['delivery_type'],
                    'fixed_delivery_fee' => $s['store']['fixed_delivery_fee'],
                ]
            );

            // Seed delivery fees for each store
            $store->deliveryFees()->delete();
            $store->deliveryFees()->createMany([
                ['wilayah' => 'Kampus Gunung Kelua', 'fee' => 3000],
                ['wilayah' => 'Samarinda Kota', 'fee' => 7000],
                ['wilayah' => 'Samarinda Ulu', 'fee' => 9000],
                ['wilayah' => 'Samarinda Seberang', 'fee' => 12000],
            ]);

            $sellers[] = $user;
            $stores[] = $store;
        }

        // 5. Seed 100 Products across 3 stores and 9 categories
        $this->command->info('Creating 100 Dummy Products...');
        $productCategories = ProductCategory::all();
        
        $templates = [
            'makanan-dan-minuman' => [
                ['name' => 'Es Kopi Aren FEB', 'price_min' => 12000, 'price_max' => 18000, 'desc' => 'Kopi espresso robusta dipadu susu segar dan gula aren asli.'],
                ['name' => 'Dimsum Ayam Homemade', 'price_min' => 15000, 'price_max' => 25000, 'desc' => 'Dimsum isi ayam padat isi 5 pcs dengan saus sambal khas.'],
                ['name' => 'Roti Bakar Cokelat Lumer', 'price_min' => 12000, 'price_max' => 20000, 'desc' => 'Roti bakar mentega dengan isi cokelat melimpah dan taburan keju.'],
                ['name' => 'Brownies Panggang Sekat', 'price_min' => 35000, 'price_max' => 65000, 'desc' => 'Kue brownies sekat renyah di luar, lembut di dalam.'],
                ['name' => 'Salad Buah Segar Premium', 'price_min' => 15000, 'price_max' => 30000, 'desc' => 'Salad buah dingin disiram dressing mayo-yogurt parutan keju.'],
                ['name' => 'Thai Tea Kemasan Botol', 'price_min' => 8000, 'price_max' => 12000, 'desc' => 'Minuman Thai tea manis dingin menyegarkan dalam botol.'],
                ['name' => 'Cireng Bumbu Rujak Pedas', 'price_min' => 10000, 'price_max' => 15000, 'desc' => 'Cireng goreng renyah isi 10 pcs lengkap dengan bumbu rujak pedas manis.'],
            ],
            'fashion' => [
                ['name' => 'Kaos Alumni FEB Unmul', 'price_min' => 75000, 'price_max' => 95000, 'desc' => 'Kaos cotton combed 30s premium sablon logo alumni FEB Unmul.'],
                ['name' => 'Hoodie Fleece Unmul Hijau', 'price_min' => 160000, 'price_max' => 230000, 'desc' => 'Hoodie bahan fleece tebal warna hijau botol khas Unmul.'],
                ['name' => 'Batik Katun Motif Kaltim', 'price_min' => 110000, 'price_max' => 200000, 'desc' => 'Kemeja lengan pendek bahan katun adem motif tameng Kaltim.'],
                ['name' => 'Hijab Voal Laser Cut', 'price_min' => 35000, 'price_max' => 60000, 'desc' => 'Jilbab voal premium motif polos tepian laser cut rapi.'],
                ['name' => 'Totebag Canvas Aesthetic', 'price_min' => 25000, 'price_max' => 45000, 'desc' => 'Tas kanvas jinjing tebal dengan desain grafis minimalis.'],
                ['name' => 'Celana Chino Slimfit Hitam', 'price_min' => 90000, 'price_max' => 140000, 'desc' => 'Celana chino panjang elastis nyaman untuk kuliah maupun magang.'],
            ],
            'elektronik' => [
                ['name' => 'Powerbank Slim 10000mAh', 'price_min' => 99000, 'price_max' => 145000, 'desc' => 'Powerbank tipis dual output fast charging aman untuk handphone.'],
                ['name' => 'Mouse Wireless Silent Click', 'price_min' => 45000, 'price_max' => 85000, 'desc' => 'Mouse bluetooth senyap tanpa suara klik, baterai awet.'],
                ['name' => 'Earphone Bluetooth TWS', 'price_min' => 110000, 'price_max' => 220000, 'desc' => 'TWS mini earphone stereo suara jernih bass mantap.'],
                ['name' => 'Stand Holder HP Aluminium', 'price_min' => 25000, 'price_max' => 50000, 'desc' => 'Dudukan handphone meja bahan besi kokoh anti slip.'],
                ['name' => 'Lampu Belajar LED Rechargeable', 'price_min' => 40000, 'price_max' => 80000, 'desc' => 'Lampu meja portable dengan 3 tingkat keterangan baterai cas.'],
            ],
            'buku' => [
                ['name' => 'Buku Pengantar Akuntansi I', 'price_min' => 85000, 'price_max' => 120000, 'desc' => 'Buku wajib mahasiswa FEB karangan dosen akuntansi senior.'],
                ['name' => 'Buku Manajemen Keuangan UMKM', 'price_min' => 70000, 'price_max' => 100000, 'desc' => 'Panduan praktis pengelolaan kas dan pelaporan keuangan sederhana.'],
                ['name' => 'Binder Catatan Ring Kulit', 'price_min' => 45000, 'price_max' => 80000, 'desc' => 'Buku binder catatan kuliah ukuran A5 dengan cover kulit.'],
                ['name' => 'Set Stabilo Pastel Highlighter', 'price_min' => 12000, 'price_max' => 22000, 'desc' => 'Satu set highlighter isi 6 warna pastel estetik.'],
            ],
            'kerajinan' => [
                ['name' => 'Buket Bunga Flanel Wisuda', 'price_min' => 40000, 'price_max' => 110000, 'desc' => 'Hadiah wisuda buket bunga flanel handmade awet bertahun-tahun.'],
                ['name' => 'Lilin Aromaterapi Essential Oil', 'price_min' => 18000, 'price_max' => 35000, 'desc' => 'Lilin aromatik wangi relaksasi lavender membantu konsentrasi belajar.'],
                ['name' => 'Gantungan Kunci Rajut Hewan', 'price_min' => 10000, 'price_max' => 20000, 'desc' => 'Gantungan kunci rajut handmade karakter lucu gantungan tas.'],
            ],
            'otomotif' => [
                ['name' => 'Parfum Mobil Scent Kopi', 'price_min' => 15000, 'price_max' => 30000, 'desc' => 'Pengharum gantungan mobil wangi kopi hitam alami tahan 30 hari.'],
                ['name' => 'Kanebo Serat Tinggi Super', 'price_min' => 12000, 'price_max' => 25000, 'desc' => 'Lap kanebo tebal penyerap air tinggi untuk cuci motor.'],
                ['name' => 'Cover Motor Anti Air & Debu', 'price_min' => 65000, 'price_max' => 120000, 'desc' => 'Mantel pelindung motor matic bahan parasut kuat.'],
            ],
            'pertanian' => [
                ['name' => 'Pupuk Organik Kompos Super', 'price_min' => 10000, 'price_max' => 20000, 'desc' => 'Pupuk kompos organik siap pakai penyubur tanaman hias.'],
                ['name' => 'Benih Cabai Rawit Unggul', 'price_min' => 8000, 'price_max' => 15000, 'desc' => 'Satu kemasan benih cabe rawit pedas tinggi persentase tumbuh.'],
            ],
            'umkm' => [
                ['name' => 'Madu Hutan Asli Kaltim 250ml', 'price_min' => 65000, 'price_max' => 95000, 'desc' => 'Madu murni hasil hutan tropis Kalimantan Timur kaya manfaat.'],
                ['name' => 'Amplang Ikan Tenggiri Gurih', 'price_min' => 20000, 'price_max' => 40000, 'desc' => 'Cemilan kerupuk amplang gurih rasa ikan tenggiri asli.'],
            ]
        ];

        for ($i = 1; $i <= 100; $i++) {
            // Determine Category
            $category = $productCategories[$i % $productCategories->count()];
            $catSlug = $category->slug;

            // Pick Template
            $pool = $templates[$catSlug] ?? $templates['makanan-dan-minuman'];
            $tpl = $pool[array_rand($pool)];

            // Determine Store (Owner)
            $store = $stores[$i % count($stores)];

            $name = $tpl['name'] . ' ' . $i;
            $price = rand($tpl['price_min'], $tpl['price_max']);
            $stock = rand(5, 40);

            Product::create([
                'store_id' => $store->id,
                'product_category_id' => $category->id,
                'name' => $name,
                'slug' => Str::slug($name . '-' . uniqid()),
                'description' => $tpl['desc'] . ' Ini adalah barang dummy berkualitas nomor ' . $i . '.',
                'price' => $price,
                'stock' => $stock,
                'status' => 'active',
                'is_featured' => ($i % 10 === 0), // Every 10th product is featured
            ]);
        }

        // 6. Seed Services (10 Jasa)
        $this->command->info('Creating 10 Dummy Services...');
        $serviceCategories = ServiceCategory::all();
        $serviceTemplates = [
            ['name' => 'Jasa Audit & Penyusunan LK UMKM', 'price' => 500000, 'desc' => 'Membantu penyusunan laporan keuangan neraca & laba rugi bulanan.'],
            ['name' => 'Jasa Desain Feed Promosi Instagram', 'price' => 150000, 'desc' => 'Desain postingan grafis promosi produk instagram bisnis Anda.'],
            ['name' => 'Jasa Konsultasi Pajak SPT Tahunan', 'price' => 250000, 'desc' => 'Pendampingan pelaporan SPT tahunan orang pribadi / badan.'],
            ['name' => 'Fotografi Katalog Produk UMKM', 'price' => 300000, 'desc' => 'Jasa foto produk studio mini agar jualan Anda tampil profesional.'],
        ];

        for ($j = 1; $j <= 10; $j++) {
            $category = $serviceCategories[$j % $serviceCategories->count()];
            $tpl = $serviceTemplates[$j % count($serviceTemplates)];
            $store = $stores[$j % count($stores)];

            $name = $tpl['name'] . ' ' . $j;

            Service::create([
                'store_id' => $store->id,
                'service_category_id' => $category->id,
                'name' => $name,
                'slug' => Str::slug($name . '-' . uniqid()),
                'description' => $tpl['desc'] . ' Melayani pengerjaan cepat dan terpercaya.',
                'price_from' => $tpl['price'],
                'lokasi_layanan' => 'Samarinda & Online',
                'status' => 'active',
                'is_featured' => ($j % 3 === 0),
            ]);
        }

        // 7. Seed completed orders & reviews for stats visualization
        $this->command->info('Seeding Order History for Dashboards...');
        $defaultBuyer = User::where('email', 'buyer@perkasa.test')->first();
        if ($defaultBuyer) {
            $someProduct = Product::first();
            $store = $someProduct->store;

            // Clean up existing duplicate order to make seeder re-runnable
            $existingOrder = Order::where('order_number', 'ORD-' . date('Ymd') . '-DUMMY1')->first();
            if ($existingOrder) {
                $existingOrder->items()->delete();
                $existingOrder->statusLogs()->delete();
                $existingOrder->delete();
            }

            $order = Order::create([
                'user_id' => $defaultBuyer->id,
                'store_id' => $store->id,
                'order_number' => 'ORD-' . date('Ymd') . '-DUMMY1',
                'subtotal' => $someProduct->price * 2,
                'biaya_antar' => 3000,
                'total' => ($someProduct->price * 2) + 3000,
                'status' => 'selesai',
                'payment_method' => 'COD',
                'nama_penerima' => $defaultBuyer->name,
                'telepon_penerima' => '081234567890',
                'alamat_penerima' => 'Gedung Dekanat FEB Lt.1',
                'wilayah_antar' => 'Kampus Gunung Kelua',
                'catatan' => 'Pesanan dummy histori.',
                'latitude' => -0.468892,
                'longitude' => 117.161245,
            ]);

            $orderItem = $order->items()->create([
                'product_id' => $someProduct->id,
                'name' => $someProduct->name,
                'quantity' => 2,
                'price' => $someProduct->price,
            ]);

            $order->statusLogs()->createMany([
                ['status' => 'menunggu_konfirmasi', 'description' => 'Pesanan dibuat.', 'changed_by' => $defaultBuyer->id],
                ['status' => 'selesai', 'description' => 'Pesanan diselesaikan.', 'changed_by' => $store->alumniProfile->user_id],
            ]);

            Review::create([
                'user_id' => $defaultBuyer->id,
                'store_id' => $store->id,
                'order_item_id' => $orderItem->id,
                'reviewable_type' => Product::class,
                'reviewable_id' => $someProduct->id,
                'rating' => 5,
                'comment' => 'Produk dummy sangat berkualitas dan pelayanan cepat!',
                'reply' => 'Terima kasih banyak atas feedback positifnya!',
                'replied_at' => now(),
            ]);
        }

        // 8. Seed 2 pending users for Admin's action
        $userPending1 = User::updateOrCreate(
            ['email' => 'budi@perkasa.test'],
            ['name' => 'Budi Santoso', 'password' => Hash::make('password123')]
        );
        $userPending1->assignRole('alumni_pembeli');
        $userPending1->profile()->updateOrCreate(
            ['user_id' => $userPending1->id],
            [
                'nim' => '1801015050',
                'program_studi' => 'S1 Manajemen',
                'tahun_masuk' => 2018,
                'tahun_lulus' => 2022,
                'whatsapp' => '081234567812',
                'status_verifikasi' => 'pending',
                'badge_verified' => false,
            ]
        );

        $userPending2 = User::updateOrCreate(
            ['email' => 'siti@perkasa.test'],
            ['name' => 'Siti Aminah', 'password' => Hash::make('password123')]
        );
        $userPending2->assignRole('alumni_penjual');
        $userPending2->profile()->updateOrCreate(
            ['user_id' => $userPending2->id],
            [
                'nim' => '1801015060',
                'program_studi' => 'S1 Akuntansi',
                'tahun_masuk' => 2018,
                'tahun_lulus' => 2022,
                'whatsapp' => '081234567822',
                'status_verifikasi' => 'pending',
                'badge_verified' => false,
            ]
        );

        $this->command->info('Seeding product and service media...');
        $this->call(ProductMediaAndCategorySeeder::class);

        $this->command->info('Large demo dataset successfully seeded!');
    }
}
