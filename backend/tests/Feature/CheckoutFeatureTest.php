<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ProductCategory;
use App\Models\Product;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected $buyer;
    protected $unverifiedBuyer;
    protected $seller1;
    protected $seller2;
    protected $product1;
    protected $product2;
    protected $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        // Verified Buyer
        $this->buyer = User::create([
            'name' => 'Buyer Verified',
            'email' => 'buyer@example.com',
            'password' => bcrypt('password123')
        ]);
        $this->buyer->assignRole('alumni_pembeli');
        $this->buyer->profile()->create([
            'nim' => '1801015112',
            'program_studi' => 'S1 Manajemen',
            'tahun_masuk' => 2018,
            'tahun_lulus' => 2022,
            'whatsapp' => '081234567890',
            'status_verifikasi' => 'verified',
            'badge_verified' => true
        ]);

        // Unverified Buyer
        $this->unverifiedBuyer = User::create([
            'name' => 'Buyer Unverified',
            'email' => 'buyer2@example.com',
            'password' => bcrypt('password123')
        ]);
        $this->unverifiedBuyer->assignRole('alumni_pembeli');
        $this->unverifiedBuyer->profile()->create([
            'nim' => '1801015113',
            'program_studi' => 'S1 Manajemen',
            'tahun_masuk' => 2018,
            'tahun_lulus' => 2022,
            'whatsapp' => '081234567894',
            'status_verifikasi' => 'pending',
            'badge_verified' => false
        ]);

        // Seller 1 (Fixed delivery fee)
        $this->seller1 = User::create([
            'name' => 'Seller Satu',
            'email' => 'seller1@example.com',
            'password' => bcrypt('password123')
        ]);
        $this->seller1->assignRole('alumni_pembeli');
        $profile1 = $this->seller1->profile()->create([
            'nim' => '1801015111',
            'program_studi' => 'S1 Akuntansi',
            'tahun_masuk' => 2018,
            'tahun_lulus' => 2022,
            'whatsapp' => '081234567891',
            'status_verifikasi' => 'verified',
            'badge_verified' => true
        ]);
        $store1 = $profile1->store()->create([
            'name' => 'Store Satu (Fixed)',
            'kategori_usaha' => 'Fashion',
            'whatsapp' => '081234567891',
            'kota' => 'Samarinda',
            'status' => 'active',
            'delivery_type' => 'fixed',
            'fixed_delivery_fee' => 10000,
            'tahun_berdiri' => 2024
        ]);

        // Seller 2 (Per wilayah delivery fee)
        $this->seller2 = User::create([
            'name' => 'Seller Dua',
            'email' => 'seller2@example.com',
            'password' => bcrypt('password123')
        ]);
        $this->seller2->assignRole('alumni_pembeli');
        $profile2 = $this->seller2->profile()->create([
            'nim' => '1801015222',
            'program_studi' => 'S1 Akuntansi',
            'tahun_masuk' => 2018,
            'tahun_lulus' => 2022,
            'whatsapp' => '081234567892',
            'status_verifikasi' => 'verified',
            'badge_verified' => true
        ]);
        $store2 = $profile2->store()->create([
            'name' => 'Store Dua (Per Wilayah)',
            'kategori_usaha' => 'Makanan dan Minuman',
            'whatsapp' => '081234567892',
            'kota' => 'Samarinda',
            'status' => 'active',
            'delivery_type' => 'per_wilayah',
            'tahun_berdiri' => 2024
        ]);
        $store2->deliveryFees()->create([
            'wilayah' => 'Samarinda Ulu',
            'fee' => 15000
        ]);

        // Category
        $this->category = ProductCategory::create([
            'name' => 'Makanan',
            'slug' => 'makanan',
            'is_active' => true
        ]);

        // Products
        $this->product1 = Product::create([
            'store_id' => $store1->id,
            'product_category_id' => $this->category->id,
            'name' => 'Baju Keren',
            'slug' => 'baju-keren',
            'description' => 'Baju kaos FEB keren',
            'price' => 50000,
            'stock' => 10,
            'status' => 'active'
        ]);

        $this->product2 = Product::create([
            'store_id' => $store2->id,
            'product_category_id' => $this->category->id,
            'name' => 'Roti Manis',
            'slug' => 'roti-manis',
            'description' => 'Roti rasa manis',
            'price' => 10000,
            'stock' => 20,
            'status' => 'active'
        ]);
    }

    /**
     * Test unverified user cannot checkout.
     */
    public function test_unverified_user_cannot_checkout()
    {
        $token = $this->unverifiedBuyer->createToken('auth_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token"
        ])->postJson('/api/checkout', [
            'nama_penerima' => 'Nama Penerima',
            'telepon_penerima' => '08123',
            'alamat_penerima' => 'Alamat Lengkap',
        ]);

        $response->assertStatus(403);
    }

    /**
     * Test successful checkout with order splitting, stock reduction, and cart clear.
     */
    public function test_successful_checkout_with_order_splitting()
    {
        // Add both items to cart
        $cart = $this->buyer->cart()->create();
        $cart->items()->create([
            'product_id' => $this->product1->id,
            'quantity' => 2
        ]);
        $cart->items()->create([
            'product_id' => $this->product2->id,
            'quantity' => 3
        ]);

        $token = $this->buyer->createToken('auth_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token"
        ])->postJson('/api/checkout', [
            'nama_penerima' => 'John Doe',
            'telepon_penerima' => '081234567899',
            'alamat_penerima' => 'Jl. Mulawarman No 12',
            'wilayah_antar' => 'Samarinda Ulu'
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'message',
            'orders' => [
                ['id', 'order_number', 'total']
            ]
        ]);
        $response->assertJsonCount(2, 'orders');

        // Check stock reduction
        $this->assertEquals(8, $this->product1->fresh()->stock); // 10 - 2
        $this->assertEquals(17, $this->product2->fresh()->stock); // 20 - 3

        // Check cart cleared
        $this->assertCount(0, $cart->fresh()->items);

        // Verify database records for orders
        $this->assertDatabaseHas('orders', [
            'user_id' => $this->buyer->id,
            'store_id' => $this->product1->store_id,
            'subtotal' => 100000.00, // 50000 * 2
            'biaya_antar' => 10000.00, // fixed
            'total' => 110000.00
        ]);

        $this->assertDatabaseHas('orders', [
            'user_id' => $this->buyer->id,
            'store_id' => $this->product2->store_id,
            'subtotal' => 30000.00, // 10000 * 3
            'biaya_antar' => 15000.00, // Samarinda Ulu
            'total' => 45000.00
        ]);
    }

    /**
     * Test checkout fails if region is not served by store using delivery fees per wilayah.
     */
    public function test_checkout_fails_if_region_not_served()
    {
        $cart = $this->buyer->cart()->create();
        $cart->items()->create([
            'product_id' => $this->product2->id, // Store 2 using per_wilayah
            'quantity' => 1
        ]);

        $token = $this->buyer->createToken('auth_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token"
        ])->postJson('/api/checkout', [
            'nama_penerima' => 'John Doe',
            'telepon_penerima' => '081234567899',
            'alamat_penerima' => 'Jl. Mulawarman No 12',
            'wilayah_antar' => 'Samarinda Seberang' // Store 2 doesn't serve Samarinda Seberang
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'message' => 'Toko Store Dua (Per Wilayah) tidak melayani pengantaran ke wilayah Samarinda Seberang.'
        ]);
    }
}
