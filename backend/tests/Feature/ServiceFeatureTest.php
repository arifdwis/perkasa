<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Store;
use App\Models\ServiceCategory;
use App\Models\Service;
use App\Models\ServiceImage;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ServiceFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $sellerUser;
    protected $otherSellerUser;
    protected $buyerUser;
    protected $category;
    protected $sellerStore;
    protected $otherStore;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        // 1. Create Admin
        $this->admin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password123')
        ]);
        $this->admin->assignRole('super_admin');

        // 2. Create Category
        $this->category = ServiceCategory::create([
            'name' => 'Konsultan Pajak',
            'slug' => 'konsultan-pajak',
            'is_active' => true
        ]);

        // 3. Create Seller User with Active Store
        $this->sellerUser = User::create([
            'name' => 'Seller Alumni',
            'email' => 'seller@example.com',
            'password' => bcrypt('password123')
        ]);
        $this->sellerUser->assignRole('alumni_penjual');
        $profile = $this->sellerUser->profile()->create([
            'nim' => '1801015333',
            'program_studi' => 'S1 Manajemen',
            'tahun_masuk' => 2018,
            'tahun_lulus' => 2022,
            'whatsapp' => '081234567833',
            'status_verifikasi' => 'verified',
            'badge_verified' => true
        ]);
        $this->sellerStore = $profile->store()->create([
            'name' => 'Konsultasi Alumni',
            'kategori_usaha' => 'UMKM',
            'whatsapp' => '081234567833',
            'kota' => 'Samarinda',
            'tahun_berdiri' => 2025,
            'status' => 'active',
            'delivery_type' => 'fixed'
        ]);

        // 4. Create Other Seller User with Active Store
        $this->otherSellerUser = User::create([
            'name' => 'Other Seller',
            'email' => 'other@example.com',
            'password' => bcrypt('password123')
        ]);
        $this->otherSellerUser->assignRole('alumni_penjual');
        $otherProfile = $this->otherSellerUser->profile()->create([
            'nim' => '1801015444',
            'program_studi' => 'S1 Akuntansi',
            'tahun_masuk' => 2018,
            'tahun_lulus' => 2022,
            'whatsapp' => '081234567844',
            'status_verifikasi' => 'verified',
            'badge_verified' => true
        ]);
        $this->otherStore = $otherProfile->store()->create([
            'name' => 'Akuntan Alumni',
            'kategori_usaha' => 'UMKM',
            'whatsapp' => '081234567844',
            'kota' => 'Samarinda',
            'tahun_berdiri' => 2024,
            'status' => 'active',
            'delivery_type' => 'fixed'
        ]);

        // 5. Create Buyer User (Verified Alumni without Store)
        $this->buyerUser = User::create([
            'name' => 'Buyer Alumni',
            'email' => 'buyer@example.com',
            'password' => bcrypt('password123')
        ]);
        $this->buyerUser->assignRole('alumni_pembeli');
        $this->buyerUser->profile()->create([
            'nim' => '1801015555',
            'program_studi' => 'S1 Ekonomi Pembangunan',
            'tahun_masuk' => 2018,
            'tahun_lulus' => 2022,
            'whatsapp' => '081234567855',
            'status_verifikasi' => 'verified',
            'badge_verified' => true
        ]);

        Storage::fake('public');
    }

    /**
     * Test public can view active services listing.
     */
    public function test_public_can_view_active_services()
    {
        // Active Service
        Service::create([
            'store_id' => $this->sellerStore->id,
            'service_category_id' => $this->category->id,
            'name' => 'Audit Laporan Keuangan',
            'slug' => 'audit-laporan-keuangan',
            'description' => 'Jasa audit terpercaya',
            'price_from' => 5000000,
            'lokasi_layanan' => 'Samarinda',
            'status' => 'active'
        ]);

        // Inactive Service
        Service::create([
            'store_id' => $this->sellerStore->id,
            'service_category_id' => $this->category->id,
            'name' => 'Konsultasi Pajak Perorangan',
            'slug' => 'konsultasi-pajak-perorangan',
            'description' => 'Bimbingan SPT Tahunan',
            'price_from' => 500000,
            'lokasi_layanan' => 'Online',
            'status' => 'inactive'
        ]);

        $response = $this->getJson('/api/services');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.name', 'Audit Laporan Keuangan');
    }

    /**
     * Test public can view service details via slug.
     */
    public function test_public_can_view_service_detail()
    {
        $service = Service::create([
            'store_id' => $this->sellerStore->id,
            'service_category_id' => $this->category->id,
            'name' => 'Audit Laporan Keuangan',
            'slug' => 'audit-laporan-keuangan',
            'description' => 'Jasa audit terpercaya',
            'price_from' => 5000000,
            'lokasi_layanan' => 'Samarinda',
            'status' => 'active'
        ]);

        $response = $this->getJson("/api/services/audit-laporan-keuangan");

        $response->assertStatus(200);
        $response->assertJsonPath('service.name', 'Audit Laporan Keuangan');
    }

    /**
     * Test public cannot view inactive service or service from inactive store.
     */
    public function test_public_restricted_from_inactive_service_and_store()
    {
        // 1. Inactive service
        $inactiveService = Service::create([
            'store_id' => $this->sellerStore->id,
            'service_category_id' => $this->category->id,
            'name' => 'Audit Laporan Keuangan',
            'slug' => 'audit-laporan-keuangan',
            'description' => 'Jasa audit terpercaya',
            'price_from' => 5000000,
            'lokasi_layanan' => 'Samarinda',
            'status' => 'inactive'
        ]);

        $response = $this->getJson("/api/services/audit-laporan-keuangan");
        $response->assertStatus(403);

        // 2. Service from pending store
        $this->sellerStore->update(['status' => 'pending']);
        $activeService = Service::create([
            'store_id' => $this->sellerStore->id,
            'service_category_id' => $this->category->id,
            'name' => 'Penyusunan SPT',
            'slug' => 'penyusunan-spt',
            'description' => 'Penyusunan pajak pribadi',
            'price_from' => 300000,
            'lokasi_layanan' => 'Samarinda',
            'status' => 'active'
        ]);

        $response2 = $this->getJson("/api/services/penyusunan-spt");
        $response2->assertStatus(403);
    }

    /**
     * Test seller CRUD operations on services.
     */
    public function test_seller_can_crud_own_services()
    {
        $token = $this->sellerUser->createToken('auth_token')->plainTextToken;

        // 1. CREATE
        $createResponse = $this->withHeaders([
            'Authorization' => "Bearer $token"
        ])->postJson('/api/seller/services', [
            'name' => 'Audit Laporan Keuangan',
            'service_category_id' => $this->category->id,
            'description' => 'Jasa audit terpercaya',
            'price_from' => 5000000,
            'lokasi_layanan' => 'Samarinda',
            'status' => 'active'
        ]);

        $createResponse->assertStatus(201);
        $createResponse->assertJsonPath('service.slug', 'audit-laporan-keuangan');
        $this->assertDatabaseHas('services', ['name' => 'Audit Laporan Keuangan']);

        $service = Service::where('name', 'Audit Laporan Keuangan')->first();

        // 2. READ (List & Detail)
        $listResponse = $this->withHeaders([
            'Authorization' => "Bearer $token"
        ])->getJson('/api/seller/services');

        $listResponse->assertStatus(200);
        $listResponse->assertJsonCount(1);

        $detailResponse = $this->withHeaders([
            'Authorization' => "Bearer $token"
        ])->getJson("/api/seller/services/{$service->id}");

        $detailResponse->assertStatus(200);
        $detailResponse->assertJsonPath('service.name', 'Audit Laporan Keuangan');

        // 3. UPDATE
        $updateResponse = $this->withHeaders([
            'Authorization' => "Bearer $token"
        ])->putJson("/api/seller/services/{$service->id}", [
            'name' => 'Audit Laporan Keuangan Terakreditasi',
            'service_category_id' => $this->category->id,
            'description' => 'Audit kap akreditasi a',
            'price_from' => 6000000,
            'lokasi_layanan' => 'Samarinda & Balikpapan',
            'status' => 'inactive'
        ]);

        $updateResponse->assertStatus(200);
        $this->assertDatabaseHas('services', [
            'name' => 'Audit Laporan Keuangan Terakreditasi',
            'price_from' => 6000000.00,
            'status' => 'inactive'
        ]);

        // 4. DELETE
        $deleteResponse = $this->withHeaders([
            'Authorization' => "Bearer $token"
        ])->deleteJson("/api/seller/services/{$service->id}");

        $deleteResponse->assertStatus(200);
        $this->assertDatabaseMissing('services', ['id' => $service->id]);
    }

    /**
     * Test sellers cannot CRUD other sellers' services.
     */
    public function test_seller_cannot_modify_other_sellers_service()
    {
        $service = Service::create([
            'store_id' => $this->otherStore->id,
            'service_category_id' => $this->category->id,
            'name' => 'Pajak PT Keren',
            'slug' => 'pajak-pt-keren',
            'description' => 'Jasa urus pajak tahunan perusahaan',
            'price_from' => 2000000,
            'lokasi_layanan' => 'Samarinda',
            'status' => 'active'
        ]);

        $token = $this->sellerUser->createToken('auth_token')->plainTextToken;

        // Try Update
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token"
        ])->putJson("/api/seller/services/{$service->id}", [
            'name' => 'Hacked Service',
            'service_category_id' => $this->category->id,
            'description' => 'Attempted hijack',
            'price_from' => 1000,
            'lokasi_layanan' => 'Hacked',
            'status' => 'active'
        ]);
        $response->assertStatus(403);

        // Try Delete
        $response2 = $this->withHeaders([
            'Authorization' => "Bearer $token"
        ])->deleteJson("/api/seller/services/{$service->id}");
        $response2->assertStatus(403);
    }

    /**
     * Test uploading primary image and portfolio images.
     */
    public function test_seller_can_manage_service_images()
    {
        $service = Service::create([
            'store_id' => $this->sellerStore->id,
            'service_category_id' => $this->category->id,
            'name' => 'Audit Laporan Keuangan',
            'slug' => 'audit-laporan-keuangan',
            'description' => 'Jasa audit terpercaya',
            'price_from' => 5000000,
            'lokasi_layanan' => 'Samarinda',
            'status' => 'active'
        ]);

        $token = $this->sellerUser->createToken('auth_token')->plainTextToken;

        // 1. Upload Primary Image
        $file = UploadedFile::fake()->image('primary.jpg');
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token"
        ])->postJson("/api/seller/services/{$service->id}/image", [
            'image' => $file
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('service_images', [
            'service_id' => $service->id,
            'is_primary' => true
        ]);

        // 2. Upload Portfolio Images (Max 5 constraint check)
        $file1 = UploadedFile::fake()->image('p1.jpg');
        $file2 = UploadedFile::fake()->image('p2.jpg');
        $file3 = UploadedFile::fake()->image('p3.jpg');
        $file4 = UploadedFile::fake()->image('p4.jpg');
        $file5 = UploadedFile::fake()->image('p5.jpg');
        $file6 = UploadedFile::fake()->image('p6.jpg');

        // Post 3 files -> Should succeed
        $portfolioResponse = $this->withHeaders([
            'Authorization' => "Bearer $token"
        ])->postJson("/api/seller/services/{$service->id}/portfolio", [
            'images' => [$file1, $file2, $file3]
        ]);
        $portfolioResponse->assertStatus(200);
        $this->assertEquals(3, $service->images()->where('is_primary', false)->count());

        // Post 3 more files -> Total 6, should fail (max limit 5)
        $failedResponse = $this->withHeaders([
            'Authorization' => "Bearer $token"
        ])->postJson("/api/seller/services/{$service->id}/portfolio", [
            'images' => [$file4, $file5, $file6]
        ]);
        $failedResponse->assertStatus(422);

        // 3. Delete Specific Image
        $imageToDelete = $service->images()->where('is_primary', false)->first();
        $deleteResponse = $this->withHeaders([
            'Authorization' => "Bearer $token"
        ])->deleteJson("/api/seller/services/{$service->id}/images/{$imageToDelete->id}");

        $deleteResponse->assertStatus(200);
        $this->assertDatabaseMissing('service_images', ['id' => $imageToDelete->id]);
    }
}
