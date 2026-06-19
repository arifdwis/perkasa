<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ValidationTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);
        $this->user->assignRole('alumni_pembeli');
        $this->user->profile()->create([
            'nim' => '1801015111',
            'program_studi' => 'S1 Manajemen',
            'tahun_masuk' => 2018,
            'tahun_lulus' => 2022,
            'whatsapp' => '081234567899',
            'status_verifikasi' => 'verified',
            'badge_verified' => true,
        ]);
    }

    /**
     * Test register validation fails on weak password or missing fields.
     */
    public function test_register_validation_fails_on_missing_fields()
    {
        $response = $this->postJson('/api/register', [
            'name' => '',
            'email' => 'not-an-email',
            'password' => '123',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    /**
     * Test register fails if NIM or Email is already registered.
     */
    public function test_register_fails_on_duplicate_identity()
    {
        // NIM '1801015111' is already taken by $this->user profile in setUp
        $response = $this->postJson('/api/register', [
            'name' => 'Another User',
            'email' => 'another@example.com',
            'password' => 'Password123!',
            'nim' => '1801015111', // taken
            'program_studi' => 'S1 Akuntansi',
            'tahun_masuk' => 2019,
            'tahun_lulus' => 2023,
            'whatsapp' => '081234567812',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['nim']);
    }

    /**
     * Test checkout validation fails when recipient name or phone is empty.
     */
    public function test_checkout_validation_fails_on_missing_recipient_info()
    {
        // Create seller and product to add to cart
        $seller = User::create([
            'name' => 'Seller User',
            'email' => 'seller@example.com',
            'password' => bcrypt('password123'),
        ]);
        $sellerProfile = $seller->profile()->create([
            'nim' => '1801015998',
            'program_studi' => 'S1 Akuntansi',
            'tahun_masuk' => 2018,
            'tahun_lulus' => 2022,
            'whatsapp' => '081234567887',
            'status_verifikasi' => 'verified',
            'badge_verified' => true,
        ]);
        $store = $sellerProfile->store()->create([
            'name' => 'Seller Store',
            'kategori_usaha' => 'Makanan dan Minuman',
            'whatsapp' => '081234567887',
            'kota' => 'Samarinda',
            'status' => 'active',
            'delivery_type' => 'fixed',
            'fixed_delivery_fee' => 5000,
            'tahun_berdiri' => 2024,
        ]);
        $category = ProductCategory::create([
            'name' => 'Makanan',
            'slug' => 'makanan',
            'is_active' => true,
        ]);
        $product = Product::create([
            'store_id' => $store->id,
            'product_category_id' => $category->id,
            'name' => 'Kopi susu',
            'slug' => 'kopi-susu',
            'description' => 'Kopi susu enak',
            'price' => 15000,
            'stock' => 10,
            'status' => 'active',
        ]);

        $cart = $this->user->cart()->create();
        $cart->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $token = $this->user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->postJson('/api/checkout', [
            'nama_penerima' => '',
            'telepon_penerima' => '',
            'alamat_penerima' => '',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['nama_penerima', 'telepon_penerima', 'alamat_penerima']);
    }
}
