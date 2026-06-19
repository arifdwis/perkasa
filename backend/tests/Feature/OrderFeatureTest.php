<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected $buyer;

    protected $otherBuyer;

    protected $seller;

    protected $otherSeller;

    protected $product;

    protected $category;

    protected $order;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        // Buyer 1
        $this->buyer = User::create([
            'name' => 'Buyer Satu',
            'email' => 'buyer1@example.com',
            'password' => bcrypt('password123'),
        ]);
        $this->buyer->assignRole('alumni_pembeli');
        $this->buyer->profile()->create([
            'nim' => '1801015112',
            'program_studi' => 'S1 Manajemen',
            'tahun_masuk' => 2018,
            'tahun_lulus' => 2022,
            'whatsapp' => '081234567890',
            'status_verifikasi' => 'verified',
            'badge_verified' => true,
        ]);

        // Buyer 2
        $this->otherBuyer = User::create([
            'name' => 'Buyer Dua',
            'email' => 'buyer2@example.com',
            'password' => bcrypt('password123'),
        ]);
        $this->otherBuyer->assignRole('alumni_pembeli');
        $this->otherBuyer->profile()->create([
            'nim' => '1801015113',
            'program_studi' => 'S1 Manajemen',
            'tahun_masuk' => 2018,
            'tahun_lulus' => 2022,
            'whatsapp' => '081234567895',
            'status_verifikasi' => 'verified',
            'badge_verified' => true,
        ]);

        // Seller 1
        $this->seller = User::create([
            'name' => 'Seller Satu',
            'email' => 'seller1@example.com',
            'password' => bcrypt('password123'),
        ]);
        $this->seller->assignRole('alumni_pembeli');
        $profile1 = $this->seller->profile()->create([
            'nim' => '1801015111',
            'program_studi' => 'S1 Akuntansi',
            'tahun_masuk' => 2018,
            'tahun_lulus' => 2022,
            'whatsapp' => '081234567891',
            'status_verifikasi' => 'verified',
            'badge_verified' => true,
        ]);
        $store = $profile1->store()->create([
            'name' => 'Store Fixed',
            'kategori_usaha' => 'Fashion',
            'whatsapp' => '081234567891',
            'kota' => 'Samarinda',
            'status' => 'active',
            'delivery_type' => 'fixed',
            'fixed_delivery_fee' => 10000,
            'tahun_berdiri' => 2024,
        ]);

        // Seller 2
        $this->otherSeller = User::create([
            'name' => 'Seller Dua',
            'email' => 'seller2@example.com',
            'password' => bcrypt('password123'),
        ]);
        $this->otherSeller->assignRole('alumni_pembeli');
        $profile2 = $this->otherSeller->profile()->create([
            'nim' => '1801015222',
            'program_studi' => 'S1 Akuntansi',
            'tahun_masuk' => 2018,
            'tahun_lulus' => 2022,
            'whatsapp' => '081234567892',
            'status_verifikasi' => 'verified',
            'badge_verified' => true,
        ]);
        $profile2->store()->create([
            'name' => 'Store Other',
            'kategori_usaha' => 'Fashion',
            'whatsapp' => '081234567892',
            'kota' => 'Samarinda',
            'status' => 'active',
            'delivery_type' => 'fixed',
            'fixed_delivery_fee' => 10000,
            'tahun_berdiri' => 2024,
        ]);

        // Category
        $this->category = ProductCategory::create([
            'name' => 'Makanan',
            'slug' => 'makanan',
            'is_active' => true,
        ]);

        // Product
        $this->product = Product::create([
            'store_id' => $store->id,
            'product_category_id' => $this->category->id,
            'name' => 'Baju Keren',
            'slug' => 'baju-keren',
            'description' => 'Baju kaos FEB keren',
            'price' => 50000,
            'stock' => 10,
            'status' => 'active',
        ]);

        // Initial Order for Buyer 1 from Seller 1
        $this->order = Order::create([
            'order_number' => 'ORD-TEST-1234',
            'user_id' => $this->buyer->id,
            'store_id' => $store->id,
            'nama_penerima' => 'John Doe',
            'telepon_penerima' => '0812345678',
            'alamat_penerima' => 'Jl. Jalan 12',
            'subtotal' => 50000,
            'biaya_antar' => 10000,
            'total' => 60000,
            'payment_method' => 'COD',
            'status' => 'menunggu_konfirmasi',
        ]);

        $this->order->items()->create([
            'product_id' => $this->product->id,
            'name' => $this->product->name,
            'price' => $this->product->price,
            'quantity' => 1,
        ]);

        $this->order->statusLogs()->create([
            'status' => 'menunggu_konfirmasi',
            'description' => 'Pesanan berhasil dibuat.',
            'changed_by' => $this->buyer->id,
        ]);
    }

    /**
     * Test buyer can view their orders.
     */
    public function test_buyer_can_view_orders()
    {
        $token = $this->buyer->createToken('auth_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->getJson('/api/orders');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    /**
     * Test buyer cannot view other buyer's order.
     */
    public function test_buyer_cannot_view_other_buyers_order()
    {
        $token = $this->otherBuyer->createToken('auth_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->getJson("/api/orders/{$this->order->id}");

        $response->assertStatus(403);
    }

    /**
     * Test buyer can cancel order when status is 'menunggu_konfirmasi' and stock is restored.
     */
    public function test_buyer_can_cancel_order_under_pending_status()
    {
        $initialStock = $this->product->stock;
        $token = $this->buyer->createToken('auth_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->postJson("/api/orders/{$this->order->id}/cancel");

        $response->assertStatus(200);
        $this->assertEquals('dibatalkan', $this->order->fresh()->status);
        $this->assertEquals($initialStock + 1, $this->product->fresh()->stock); // Stock restored
    }

    /**
     * Test seller can view received orders.
     */
    public function test_seller_can_view_received_orders()
    {
        $token = $this->seller->createToken('auth_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->getJson('/api/seller/orders');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    /**
     * Test seller cannot view or update other store's orders.
     */
    public function test_seller_cannot_view_or_update_other_stores_orders()
    {
        $token = $this->otherSeller->createToken('auth_token')->plainTextToken;

        $responseGet = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->getJson("/api/seller/orders/{$this->order->id}");

        $responseGet->assertStatus(403);

        $responsePut = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->putJson("/api/seller/orders/{$this->order->id}/status", [
            'status' => 'diproses',
        ]);

        $responsePut->assertStatus(403);
    }

    /**
     * Test seller can process order.
     */
    public function test_seller_can_update_order_status()
    {
        $token = $this->seller->createToken('auth_token')->plainTextToken;

        // 1. menunggu_konfirmasi -> diproses
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->putJson("/api/seller/orders/{$this->order->id}/status", [
            'status' => 'diproses',
            'description' => 'Dikonfirmasi oleh penjual.',
        ]);

        $response->assertStatus(200);
        $this->assertEquals('diproses', $this->order->fresh()->status);
        $this->assertDatabaseHas('order_status_logs', [
            'order_id' => $this->order->id,
            'status' => 'diproses',
            'description' => 'Dikonfirmasi oleh penjual.',
        ]);

        // 2. diproses -> dalam_pengantaran
        $response2 = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->putJson("/api/seller/orders/{$this->order->id}/status", [
            'status' => 'dalam_pengantaran',
        ]);

        $response2->assertStatus(200);
        $this->assertEquals('dalam_pengantaran', $this->order->fresh()->status);

        // 3. dalam_pengantaran -> selesai
        $response3 = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->putJson("/api/seller/orders/{$this->order->id}/status", [
            'status' => 'selesai',
        ]);

        $response3->assertStatus(200);
        $this->assertEquals('selesai', $this->order->fresh()->status);
    }

    /**
     * Test illegal status transitions are rejected.
     */
    public function test_illegal_status_transitions_rejected()
    {
        $token = $this->seller->createToken('auth_token')->plainTextToken;

        // Try direct from menunggu_konfirmasi -> selesai (Illegal)
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->putJson("/api/seller/orders/{$this->order->id}/status", [
            'status' => 'selesai',
        ]);

        $response->assertStatus(400);
        $this->assertEquals('menunggu_konfirmasi', $this->order->fresh()->status);
    }
}
