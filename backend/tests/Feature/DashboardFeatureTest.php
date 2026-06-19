<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;

    protected $sellerUser;

    protected $buyerUser;

    protected $store;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        // Admin User
        $this->adminUser = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
        ]);
        $this->adminUser->assignRole('super_admin');

        // Seller User
        $this->sellerUser = User::create([
            'name' => 'Seller User',
            'email' => 'seller@example.com',
            'password' => bcrypt('password123'),
        ]);
        $this->sellerUser->assignRole('alumni_pembeli');
        $profile = $this->sellerUser->profile()->create([
            'nim' => '1801015123',
            'program_studi' => 'S1 Manajemen',
            'tahun_masuk' => 2018,
            'tahun_lulus' => 2022,
            'whatsapp' => '081234567899',
            'status_verifikasi' => 'verified',
            'badge_verified' => true,
        ]);
        $this->store = $profile->store()->create([
            'name' => 'Toko Keren',
            'kategori_usaha' => 'Fashion',
            'whatsapp' => '081234567899',
            'kota' => 'Samarinda',
            'status' => 'active',
            'delivery_type' => 'fixed',
            'fixed_delivery_fee' => 10000,
            'tahun_berdiri' => 2024,
        ]);

        // Buyer User
        $this->buyerUser = User::create([
            'name' => 'Buyer User',
            'email' => 'buyer@example.com',
            'password' => bcrypt('password123'),
        ]);
        $this->buyerUser->assignRole('alumni_pembeli');
        $this->buyerUser->profile()->create([
            'nim' => '1801015321',
            'program_studi' => 'S1 Akuntansi',
            'tahun_masuk' => 2018,
            'tahun_lulus' => 2022,
            'whatsapp' => '081234567811',
            'status_verifikasi' => 'verified',
            'badge_verified' => true,
        ]);
    }

    /**
     * Test admin dashboard stats.
     */
    public function test_admin_can_access_admin_dashboard_stats()
    {
        $token = $this->adminUser->createToken('auth_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->getJson('/api/dashboard/admin');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'total_alumni',
                'alumni_terverifikasi',
                'total_toko',
                'total_produk',
                'total_jasa',
                'total_pesanan',
                'total_transaksi_cod',
                'grafik_bulanan',
                'toko_terlaris',
                'alumni_teraktif',
            ],
        ]);
    }

    /**
     * Test regular user cannot access admin dashboard stats.
     */
    public function test_buyer_cannot_access_admin_dashboard_stats()
    {
        $token = $this->buyerUser->createToken('auth_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->getJson('/api/dashboard/admin');

        $response->assertStatus(403);
    }

    /**
     * Test seller dashboard stats.
     */
    public function test_seller_can_access_seller_dashboard_stats()
    {
        $token = $this->sellerUser->createToken('auth_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->getJson('/api/dashboard/seller');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'total_produk',
                'total_jasa',
                'total_pesanan',
                'total_penjualan',
                'rating_toko',
                'grafik_bulanan',
                'produk_terlaris',
            ],
        ]);
    }

    /**
     * Test seller dashboard returns 404 if user has no store.
     */
    public function test_seller_stats_returns_404_if_no_store()
    {
        $token = $this->buyerUser->createToken('auth_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->getJson('/api/dashboard/seller');

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'message' => 'Anda belum memiliki toko',
        ]);
    }

    /**
     * Test buyer dashboard stats.
     */
    public function test_buyer_can_access_buyer_dashboard_stats()
    {
        $token = $this->buyerUser->createToken('auth_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->getJson('/api/dashboard/buyer');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'pesanan_aktif',
                'riwayat_pesanan',
                'total_favorit',
                'ulasan_saya',
                'total_belanja',
            ],
        ]);
    }
}
