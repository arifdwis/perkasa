<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ProductCategory;
use App\Models\Product;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected $buyer;
    protected $seller;
    protected $product;
    protected $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        // Create Buyer (Verified Alumni)
        $this->buyer = User::create([
            'name' => 'Buyer Alumni',
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

        // Create Seller
        $this->seller = User::create([
            'name' => 'Seller Alumni',
            'email' => 'seller@example.com',
            'password' => bcrypt('password123')
        ]);
        $this->seller->assignRole('alumni_pembeli');
        $sellerProfile = $this->seller->profile()->create([
            'nim' => '1801015111',
            'program_studi' => 'S1 Akuntansi',
            'tahun_masuk' => 2018,
            'tahun_lulus' => 2022,
            'whatsapp' => '081234567891',
            'status_verifikasi' => 'verified',
            'badge_verified' => true
        ]);
        $store = $sellerProfile->store()->create([
            'name' => 'Store Mantap',
            'kategori_usaha' => 'Fashion',
            'whatsapp' => '081234567891',
            'kota' => 'Samarinda',
            'status' => 'active',
            'delivery_type' => 'fixed',
            'tahun_berdiri' => 2024
        ]);

        // Create Category
        $this->category = ProductCategory::create([
            'name' => 'Makanan',
            'slug' => 'makanan',
            'is_active' => true
        ]);

        // Create Product
        $this->product = Product::create([
            'store_id' => $store->id,
            'product_category_id' => $this->category->id,
            'name' => 'Roti Bakar',
            'slug' => 'roti-bakar',
            'description' => 'Roti bakar enak',
            'price' => 15000,
            'stock' => 10,
            'status' => 'active'
        ]);
    }

    /**
     * Test verified user can add item to cart.
     */
    public function test_user_can_add_item_to_cart()
    {
        $token = $this->buyer->createToken('auth_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token"
        ])->postJson('/api/cart/items', [
            'product_id' => $this->product->id,
            'quantity' => 2
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('cart_items', [
            'product_id' => $this->product->id,
            'quantity' => 2
        ]);
    }

    /**
     * Test user cannot add out of stock item to cart.
     */
    public function test_cannot_add_out_of_stock_item_to_cart()
    {
        $this->product->update(['status' => 'out_of_stock', 'stock' => 0]);
        $token = $this->buyer->createToken('auth_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token"
        ])->postJson('/api/cart/items', [
            'product_id' => $this->product->id,
            'quantity' => 1
        ]);

        $response->assertStatus(400);
    }

    /**
     * Test user cannot add quantity exceeding stock.
     */
    public function test_cannot_add_quantity_exceeding_stock()
    {
        $token = $this->buyer->createToken('auth_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token"
        ])->postJson('/api/cart/items', [
            'product_id' => $this->product->id,
            'quantity' => 15
        ]);

        $response->assertStatus(400);
    }

    /**
     * Test user can view cart items.
     */
    public function test_user_can_view_cart()
    {
        $cart = $this->buyer->cart()->create();
        $cart->items()->create([
            'product_id' => $this->product->id,
            'quantity' => 3
        ]);

        $token = $this->buyer->createToken('auth_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token"
        ])->getJson('/api/cart');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'cart_id',
            'grouped_items',
            'subtotal'
        ]);
        $response->assertJsonCount(1, 'grouped_items.0.items');
    }

    /**
     * Test user can update cart item quantity.
     */
    public function test_user_can_update_cart_item_quantity()
    {
        $cart = $this->buyer->cart()->create();
        $item = $cart->items()->create([
            'product_id' => $this->product->id,
            'quantity' => 3
        ]);

        $token = $this->buyer->createToken('auth_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token"
        ])->putJson("/api/cart/items/{$item->id}", [
            'quantity' => 5
        ]);

        $response->assertStatus(200);
        $this->assertEquals(5, $item->fresh()->quantity);
    }

    /**
     * Test user can delete cart item.
     */
    public function test_user_can_delete_cart_item()
    {
        $cart = $this->buyer->cart()->create();
        $item = $cart->items()->create([
            'product_id' => $this->product->id,
            'quantity' => 3
        ]);

        $token = $this->buyer->createToken('auth_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token"
        ])->deleteJson("/api/cart/items/{$item->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('cart_items', ['id' => $item->id]);
    }

    /**
     * Test user can clear cart.
     */
    public function test_user_can_clear_cart()
    {
        $cart = $this->buyer->cart()->create();
        $cart->items()->create([
            'product_id' => $this->product->id,
            'quantity' => 3
        ]);

        $token = $this->buyer->createToken('auth_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token"
        ])->deleteJson('/api/cart');

        $response->assertStatus(200);
        $this->assertCount(0, $cart->fresh()->items);
    }
}
