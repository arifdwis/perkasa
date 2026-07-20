<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected $sellerUser;

    protected $otherSellerUser;

    protected $productCategory;

    protected $store;

    protected $otherStore;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        // Create Categories
        $this->productCategory = ProductCategory::create([
            'name' => 'Fashion',
            'slug' => 'fashion',
            'is_active' => true,
        ]);

        // Create Seller 1 (S1 Manajemen, 2018)
        $this->sellerUser = User::create([
            'name' => 'Seller Satu',
            'email' => 'seller1@example.com',
            'password' => bcrypt('password123'),
        ]);
        $profile = $this->sellerUser->profile()->create([
            'nim' => '1801015111',
            'program_studi' => 'S1 Manajemen',
            'tahun_masuk' => 2018,
            'tahun_lulus' => 2022,
            'whatsapp' => '081234567811',
            'domisili' => 'Samarinda',
            'status_verifikasi' => 'verified',
            'badge_verified' => true,
        ]);
        $this->store = $profile->store()->create([
            'name' => 'Butik Alumni',
            'kategori_usaha' => 'Fashion',
            'whatsapp' => '081234567811',
            'kota' => 'Samarinda',
            'tahun_berdiri' => 2025,
            'status' => 'active',
            'delivery_type' => 'fixed',
        ]);

        // Create Seller 2 (S1 Akuntansi, 2019)
        $this->otherSellerUser = User::create([
            'name' => 'Seller Dua',
            'email' => 'seller2@example.com',
            'password' => bcrypt('password123'),
        ]);
        $otherProfile = $this->otherSellerUser->profile()->create([
            'nim' => '1901015222',
            'program_studi' => 'S1 Akuntansi',
            'tahun_masuk' => 2019,
            'tahun_lulus' => 2023,
            'whatsapp' => '081234567822',
            'domisili' => 'Balikpapan',
            'status_verifikasi' => 'verified',
            'badge_verified' => true,
        ]);
        $this->otherStore = $otherProfile->store()->create([
            'name' => 'Tech Service',
            'kategori_usaha' => 'Jasa',
            'whatsapp' => '081234567822',
            'kota' => 'Balikpapan',
            'tahun_berdiri' => 2026,
            'status' => 'active',
            'delivery_type' => 'fixed',
        ]);

        // Create Products
        Product::create([
            'store_id' => $this->store->id,
            'product_category_id' => $this->productCategory->id,
            'name' => 'Baju Kaos Alumni',
            'slug' => 'baju-kaos-alumni',
            'description' => 'Kaos FEB Unmul',
            'price' => 85000,
            'stock' => 20,
            'status' => 'active',
        ]);

    }

    /**
     * Test public catalog products filtering.
     */
    public function test_catalog_products_filtering()
    {
        // 1. Filter by search
        $response = $this->getJson('/api/catalog?type=product&search=Kaos');
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');

        // 2. Filter by program_studi (Owner: S1 Manajemen)
        $responseProdi = $this->getJson('/api/catalog?type=product&program_studi=S1 Manajemen');
        $responseProdi->assertStatus(200);
        $responseProdi->assertJsonCount(1, 'data');

        $responseProdiWrong = $this->getJson('/api/catalog?type=product&program_studi=S1 Akuntansi');
        $responseProdiWrong->assertStatus(200);
        $responseProdiWrong->assertJsonCount(0, 'data');
    }

    /**
     * Test public catalog stores filtering.
     */
    public function test_catalog_stores_filtering()
    {
        // 1. Filter by city (Samarinda)
        $response = $this->getJson('/api/catalog?type=store&kota=Samarinda');
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.name', 'Butik Alumni');

        // 2. Filter by program_studi (S1 Akuntansi)
        $responseProdi = $this->getJson('/api/catalog?type=store&program_studi=S1 Akuntansi');
        $responseProdi->assertStatus(200);
        $responseProdi->assertJsonCount(1, 'data');
        $responseProdi->assertJsonPath('data.0.name', 'Tech Service');
    }

    /**
     * Test public catalog alumni filtering.
     */
    public function test_catalog_alumni_filtering()
    {
        // 1. List all verified alumni
        $response = $this->getJson('/api/catalog?type=alumni');
        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');

        // 2. Filter by angkatan (tahun_masuk = 2018)
        $responseAngkatan = $this->getJson('/api/catalog?type=alumni&tahun_masuk=2018');
        $responseAngkatan->assertStatus(200);
        $responseAngkatan->assertJsonCount(1, 'data');
        $responseAngkatan->assertJsonPath('data.0.nim', '1801015111');
    }
}
