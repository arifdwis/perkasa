<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Store;
use App\Models\ProductCategory;
use App\Models\Product;
use App\Models\ProductImage;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductFeatureTest extends TestCase
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
        $this->category = ProductCategory::create([
            'name' => 'Makanan dan Minuman',
            'slug' => 'makanan-dan-minuman',
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
            'name' => 'Warung Alumni',
            'kategori_usaha' => 'Makanan dan Minuman',
            'whatsapp' => '081234567833',
            'kota' => 'Samarinda',
            'tahun_berdiri' => 2025,
            'status' => 'active',
            'delivery_type' => 'fixed',
            'fixed_delivery_fee' => 5000
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
            'name' => 'Toko Buku Alumni',
            'kategori_usaha' => 'Buku',
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
     * Test public can view active products listing.
     */
    public function test_public_can_view_active_products()
    {
        // Active Product
        Product::create([
            'store_id' => $this->sellerStore->id,
            'product_category_id' => $this->category->id,
            'name' => 'Kopi Susu Alumni',
            'slug' => 'kopi-susu-alumni',
            'description' => 'Kopi manis segar',
            'price' => 15000,
            'stock' => 10,
            'status' => 'active'
        ]);

        // Inactive Product
        Product::create([
            'store_id' => $this->sellerStore->id,
            'product_category_id' => $this->category->id,
            'name' => 'Roti Bakar',
            'slug' => 'roti-bakar',
            'description' => 'Roti bakar lezat',
            'price' => 12000,
            'stock' => 5,
            'status' => 'inactive'
        ]);

        $response = $this->getJson('/api/products');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.name', 'Kopi Susu Alumni');
    }

    /**
     * Test public can view product details via slug.
     */
    public function test_public_can_view_product_detail()
    {
        $product = Product::create([
            'store_id' => $this->sellerStore->id,
            'product_category_id' => $this->category->id,
            'name' => 'Kopi Susu Alumni',
            'slug' => 'kopi-susu-alumni',
            'description' => 'Kopi manis segar',
            'price' => 15000,
            'stock' => 10,
            'status' => 'active'
        ]);

        $response = $this->getJson("/api/products/kopi-susu-alumni");

        $response->assertStatus(200);
        $response->assertJsonPath('product.name', 'Kopi Susu Alumni');
    }

    /**
     * Test public cannot view inactive product or product from inactive store.
     */
    public function test_public_restricted_from_inactive_product_and_store()
    {
        // 1. Inactive product
        $inactiveProduct = Product::create([
            'store_id' => $this->sellerStore->id,
            'product_category_id' => $this->category->id,
            'name' => 'Kopi Susu Alumni',
            'slug' => 'kopi-susu-alumni',
            'description' => 'Kopi manis segar',
            'price' => 15000,
            'stock' => 10,
            'status' => 'inactive'
        ]);

        $response = $this->getJson("/api/products/kopi-susu-alumni");
        $response->assertStatus(403);

        // 2. Product from pending store
        $this->sellerStore->update(['status' => 'pending']);
        $activeProduct = Product::create([
            'store_id' => $this->sellerStore->id,
            'product_category_id' => $this->category->id,
            'name' => 'Kopi Hitam',
            'slug' => 'kopi-hitam',
            'description' => 'Kopi pahit segar',
            'price' => 10000,
            'stock' => 5,
            'status' => 'active'
        ]);

        $response2 = $this->getJson("/api/products/kopi-hitam");
        $response2->assertStatus(403);
    }

    /**
     * Test seller CRUD operations on products.
     */
    public function test_seller_can_crud_own_products()
    {
        $token = $this->sellerUser->createToken('auth_token')->plainTextToken;

        // 1. CREATE
        $createResponse = $this->withHeaders([
            'Authorization' => "Bearer $token"
        ])->postJson('/api/seller/products', [
            'name' => 'Kopi Susu Alumni',
            'product_category_id' => $this->category->id,
            'description' => 'Kopi manis segar',
            'price' => 15000,
            'stock' => 10,
            'status' => 'active'
        ]);

        $createResponse->assertStatus(210);
        $createResponse->assertJsonPath('product.slug', 'kopi-susu-alumni');
        $this->assertDatabaseHas('products', ['name' => 'Kopi Susu Alumni']);

        $product = Product::where('name', 'Kopi Susu Alumni')->first();

        // 2. READ (List & Detail)
        $listResponse = $this->withHeaders([
            'Authorization' => "Bearer $token"
        ])->getJson('/api/seller/products');

        $listResponse->assertStatus(200);
        $listResponse->assertJsonCount(1);

        $detailResponse = $this->withHeaders([
            'Authorization' => "Bearer $token"
        ])->getJson("/api/seller/products/{$product->id}");

        $detailResponse->assertStatus(200);
        $detailResponse->assertJsonPath('product.name', 'Kopi Susu Alumni');

        // 3. UPDATE
        $updateResponse = $this->withHeaders([
            'Authorization' => "Bearer $token"
        ])->putJson("/api/seller/products/{$product->id}", [
            'name' => 'Kopi Susu Alumni Mantap',
            'product_category_id' => $this->category->id,
            'description' => 'Kopi manis segar dan mantap',
            'price' => 16000,
            'stock' => 0, // Auto out_of_stock check
            'status' => 'active'
        ]);

        $updateResponse->assertStatus(200);
        $this->assertDatabaseHas('products', [
            'name' => 'Kopi Susu Alumni Mantap',
            'price' => 16000.00,
            'status' => 'out_of_stock' // Auto-forced to out_of_stock
        ]);

        // 4. DELETE
        $deleteResponse = $this->withHeaders([
            'Authorization' => "Bearer $token"
        ])->deleteJson("/api/seller/products/{$product->id}");

        $deleteResponse->assertStatus(200);
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    /**
     * Test sellers cannot CRUD other sellers' products.
     */
    public function test_seller_cannot_modify_other_sellers_product()
    {
        $product = Product::create([
            'store_id' => $this->otherStore->id,
            'product_category_id' => $this->category->id,
            'name' => 'Buku Akuntansi Dasar',
            'slug' => 'buku-akuntansi-dasar',
            'description' => 'Buku pegangan mahasiswa',
            'price' => 75000,
            'stock' => 3,
            'status' => 'active'
        ]);

        $token = $this->sellerUser->createToken('auth_token')->plainTextToken;

        // Try Update
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token"
        ])->putJson("/api/seller/products/{$product->id}", [
            'name' => 'Hacked Book',
            'product_category_id' => $this->category->id,
            'description' => 'Attempted hijack',
            'price' => 1000,
            'stock' => 1,
            'status' => 'active'
        ]);
        $response->assertStatus(403);

        // Try Delete
        $response2 = $this->withHeaders([
            'Authorization' => "Bearer $token"
        ])->deleteJson("/api/seller/products/{$product->id}");
        $response2->assertStatus(403);
    }

    /**
     * Test uploading primary image and gallery images.
     */
    public function test_seller_can_manage_product_images()
    {
        $product = Product::create([
            'store_id' => $this->sellerStore->id,
            'product_category_id' => $this->category->id,
            'name' => 'Kopi Susu Alumni',
            'slug' => 'kopi-susu-alumni',
            'description' => 'Kopi manis segar',
            'price' => 15000,
            'stock' => 10,
            'status' => 'active'
        ]);

        $token = $this->sellerUser->createToken('auth_token')->plainTextToken;

        // 1. Upload Primary Image
        $file = UploadedFile::fake()->image('primary.jpg');
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token"
        ])->postJson("/api/seller/products/{$product->id}/image", [
            'image' => $file
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('product_images', [
            'product_id' => $product->id,
            'is_primary' => true
        ]);

        // 2. Upload Gallery Images (Max 5 constraint check)
        $file1 = UploadedFile::fake()->image('g1.jpg');
        $file2 = UploadedFile::fake()->image('g2.jpg');
        $file3 = UploadedFile::fake()->image('g3.jpg');
        $file4 = UploadedFile::fake()->image('g4.jpg');
        $file5 = UploadedFile::fake()->image('g5.jpg');
        $file6 = UploadedFile::fake()->image('g6.jpg');

        // Post 3 files -> Should succeed
        $galleryResponse = $this->withHeaders([
            'Authorization' => "Bearer $token"
        ])->postJson("/api/seller/products/{$product->id}/gallery", [
            'images' => [$file1, $file2, $file3]
        ]);
        $galleryResponse->assertStatus(200);
        $this->assertEquals(3, $product->images()->where('is_primary', false)->count());

        // Post 3 more files -> Total 6, should fail (max limit 5)
        $failedResponse = $this->withHeaders([
            'Authorization' => "Bearer $token"
        ])->postJson("/api/seller/products/{$product->id}/gallery", [
            'images' => [$file4, $file5, $file6]
        ]);
        $failedResponse->assertStatus(422);

        // 3. Delete Specific Image
        $imageToDelete = $product->images()->where('is_primary', false)->first();
        $deleteResponse = $this->withHeaders([
            'Authorization' => "Bearer $token"
        ])->deleteJson("/api/seller/products/{$product->id}/images/{$imageToDelete->id}");

        $deleteResponse->assertStatus(200);
        $this->assertDatabaseMissing('product_images', ['id' => $imageToDelete->id]);
    }
}
