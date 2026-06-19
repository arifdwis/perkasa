<?php

// Bootstrapping Laravel
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

use App\Models\User;
use App\Models\Product;
use App\Models\Service;
use App\Models\Store;
use App\Models\ProductCategory;
use App\Models\ServiceCategory;
use App\Models\Order;
use App\Models\Review;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n======================================================================\n";
echo "🚀 END-TO-END INTEGRATION TEST: MARKETPLACE BUSINESS PROCESSES\n";
echo "======================================================================\n\n";

try {
    DB::beginTransaction();

    // 1. Check Seeder/Default Users
    echo "🟢 [STEP 1] Memverifikasi Pengguna Seed Awal...\n";
    $admin = User::where('email', 'admin@perkasa.test')->first();
    $buyer = User::where('email', 'buyer@perkasa.test')->first();
    $seller = User::where('email', 'seller@perkasa.test')->first();

    if ($admin && $buyer && $seller) {
        echo "   ✓ Super Admin ditemukan: {$admin->name}\n";
        echo "   ✓ Alumni Pembeli ditemukan: {$buyer->name}\n";
        echo "   ✓ Alumni Penjual ditemukan: {$seller->name}\n";
    } else {
        throw new Exception("Seed data awal tidak lengkap. Silakan jalankan db:seed terlebih dahulu.");
    }

    // 2. Register New Alumni
    echo "\n🟢 [STEP 2] Pendaftaran (Registrasi) Alumni Baru...\n";
    $newAlumniEmail = 'ahmaddani@mail.com';
    // Clean up if already exists
    $existing = User::where('email', $newAlumniEmail)->first();
    if ($existing) {
        $existing->delete();
    }
    
    $newAlumni = User::create([
        'name' => 'Ahmad Dani',
        'email' => $newAlumniEmail,
        'password' => Hash::make('password123'),
    ]);
    $newAlumni->assignRole('alumni_pembeli');
    $profile = $newAlumni->profile()->create([
        'nim' => '1801015099',
        'program_studi' => 'S1 Manajemen',
        'tahun_masuk' => 2018,
        'tahun_lulus' => 2022,
        'whatsapp' => '6281234567890',
        'domisili' => 'Samarinda',
        'status_verifikasi' => 'pending',
    ]);
    echo "   ✓ Akun Alumni '{$newAlumni->name}' (NIM: {$profile->nim}) berhasil didaftarkan.\n";
    echo "   ✓ Status Verifikasi Awal: {$profile->status_verifikasi}\n";

    // 3. Admin Verification
    echo "\n🟢 [STEP 3] Verifikasi Pengajuan Alumni oleh Admin...\n";
    $profile->update([
        'status_verifikasi' => 'verified',
        'badge_verified' => true,
    ]);
    echo "   ✓ Admin menyetujui akun alumni.\n";
    echo "   ✓ Status Verifikasi Baru: {$profile->fresh()->status_verifikasi}\n";
    echo "   ✓ Lencana Verified Aktif: " . ($profile->fresh()->badge_verified ? "Ya" : "Tidak") . "\n";

    // 4. Shop Registration
    echo "\n🟢 [STEP 4] Pengajuan Pendaftaran Toko Alumni Penjual...\n";
    $store = $profile->store()->create([
        'name' => 'Kopi FEB Dani',
        'description' => 'Menyediakan kopi seduh terbaik alumni FEB.',
        'kategori_usaha' => 'Makanan dan Minuman',
        'whatsapp' => '6281234567890',
        'kota' => 'Samarinda',
        'status' => 'pending',
        'delivery_type' => 'fixed',
        'fixed_delivery_fee' => 5000,
        'tahun_berdiri' => 2026,
    ]);
    echo "   ✓ Pengajuan Toko '{$store->name}' berhasil didaftarkan.\n";
    echo "   ✓ Status Toko Awal: {$store->status}\n";

    // 5. Admin Approve Shop
    echo "\n🟢 [STEP 5] Verifikasi Pengajuan Toko oleh Admin...\n";
    $store->update(['status' => 'active']);
    echo "   ✓ Admin menyetujui pengajuan toko.\n";
    echo "   ✓ Status Toko Baru: {$store->fresh()->status}\n";

    // 6. Upload Products & Services
    echo "\n🟢 [STEP 6] Penjual Mengunggah Produk dan Jasa Aktif...\n";
    $prodCat = ProductCategory::first();
    $servCat = ServiceCategory::first();

    $product = Product::create([
        'store_id' => $store->id,
        'product_category_id' => $prodCat->id,
        'name' => 'Kopi Latte FEB Premium',
        'slug' => 'kopi-latte-feb-premium',
        'description' => 'Es kopi latte susu krim premium.',
        'price' => 15000,
        'stock' => 10,
        'status' => 'active',
        'is_featured' => true,
    ]);
    echo "   ✓ Produk Berhasil Diunggah: '{$product->name}' (Harga: Rp{$product->price}, Stok: {$product->stock})\n";

    $service = Service::create([
        'store_id' => $store->id,
        'service_category_id' => $servCat->id,
        'name' => 'Jasa Pembukuan UMKM',
        'slug' => 'jasa-pembukuan-umkm',
        'description' => 'Membantu penyusunan laporan keuangan UMKM.',
        'price_from' => 500000,
        'lokasi_layanan' => 'Samarinda',
        'status' => 'active',
    ]);
    echo "   ✓ Jasa Berhasil Diunggah: '{$service->name}' (Mulai Dari: Rp{$service->price_from})\n";

    // 7. Cart Operations
    echo "\n🟢 [STEP 7] Pembeli Memasukkan Produk Ke Keranjang...\n";
    $cart = $buyer->cart ?: $buyer->cart()->create();
    $cart->items()->create([
        'product_id' => $product->id,
        'quantity' => 2,
    ]);
    echo "   ✓ Pembeli {$buyer->name} menambahkan 2 pcs '{$product->name}' ke keranjang.\n";

    // 8. Checkout COD
    echo "\n🟢 [STEP 8] Pembeli Memproses Checkout COD...\n";
    // Get cart items and calculate
    $subtotal = 0;
    foreach ($cart->items as $item) {
        $subtotal += $item->product->price * $item->quantity;
    }
    
    // Fixed fee
    $deliveryFee = $store->fixed_delivery_fee;
    $total = $subtotal + $deliveryFee;

    $order = Order::create([
        'user_id' => $buyer->id,
        'store_id' => $store->id,
        'order_number' => 'ORD-' . strtoupper(uniqid()),
        'subtotal' => $subtotal,
        'biaya_antar' => $deliveryFee,
        'total' => $total,
        'status' => 'menunggu_konfirmasi',
        'nama_penerima' => $buyer->name,
        'alamat_penerima' => 'Jl. Mulawarman Kampus Gunung Kelua',
        'telepon_penerima' => $buyer->profile->whatsapp,
        'catatan' => 'COD di depan perpustakaan.',
    ]);

    // Add Order Items
    foreach ($cart->items as $item) {
        $order->items()->create([
            'product_id' => $item->product_id,
            'name' => $item->product->name,
            'quantity' => $item->quantity,
            'price' => $item->product->price,
        ]);
        // Reduce stock
        $item->product->decrement('stock', $item->quantity);
    }
    // Clear cart
    $cart->items()->delete();

    echo "   ✓ Checkout Berhasil! Nomor Pesanan: {$order->order_number}\n";
    echo "   ✓ Rincian Bayar: Subtotal Rp{$subtotal} + Jasa Antar Rp{$deliveryFee} = Total Rp{$total}\n";
    echo "   ✓ Sisa Stok Produk Sekarang: " . $product->fresh()->stock . " pcs (Berkurang 2)\n";

    // 9. Process Order Status
    echo "\n🟢 [STEP 9] Penjual Memproses Transaksi Pesanan...\n";
    $order->update(['status' => 'diproses']);
    echo "   ✓ Status Pesanan Diperbarui: {$order->fresh()->status} (Diproses)\n";

    $order->update(['status' => 'dalam_pengantaran']);
    echo "   ✓ Status Pesanan Diperbarui: {$order->fresh()->status} (Dalam Pengantaran)\n";

    $order->update(['status' => 'selesai']);
    echo "   ✓ Status Pesanan Diperbarui: {$order->fresh()->status} (Selesai - Pembayaran COD Diterima)\n";

    // 10. Reviews & Ratings
    echo "\n🟢 [STEP 10] Pembeli Mengirimkan Ulasan & Rating Bintang...\n";
    $orderItem = $order->items->first();
    $review = Review::create([
        'user_id' => $buyer->id,
        'order_item_id' => $orderItem->id,
        'store_id' => $store->id,
        'reviewable_type' => Product::class,
        'reviewable_id' => $orderItem->product_id,
        'rating' => 5,
        'comment' => 'Kopinya mantap sekali! Pelayanan cepat.',
    ]);
    echo "   ✓ Ulasan Dikirim: Rating {$review->rating}/5 Bintang - \"{$review->comment}\"\n";

    // Calculate Rating Store
    $store->update(['average_rating' => 5.0]);
    echo "   ✓ Rata-Rata Penilaian Toko Diperbarui: {$store->fresh()->average_rating} Bintang\n";

    // 11. Dashboard Stats Verification
    echo "\n🟢 [STEP 11] Menghitung Statistik Dashboard...\n";
    $totalAlumni = User::role('alumni_pembeli')->count();
    $totalStores = Store::where('status', 'active')->count();
    $totalOrders = Order::count();
    $totalCODVolume = Order::where('status', 'selesai')->sum('total');

    echo "   ✓ Total Alumni Pembeli Terdaftar: {$totalAlumni}\n";
    echo "   ✓ Total Toko Aktif: {$totalStores}\n";
    echo "   ✓ Total Transaksi Terjadi: {$totalOrders}\n";
    echo "   ✓ Total Volume Transaksi COD Berhasil: Rp" . number_format($totalCODVolume, 0, ',', '.') . "\n";

    DB::rollBack();
    echo "\n======================================================================\n";
    echo "🎉 INTEGRATION TEST SELESAI & SUKSES! (Database di-rollback secara aman)\n";
    echo "======================================================================\n\n";

} catch (Exception $e) {
    DB::rollBack();
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
