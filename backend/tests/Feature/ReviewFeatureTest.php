<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Review;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected $buyer;

    protected $otherBuyer;

    protected $seller;

    protected $otherSeller;

    protected $product;

    protected $service;

    protected $order;

    protected $orderItem;

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
        $this->seller->assignRole('alumni_penjual');
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
        $this->otherSeller->assignRole('alumni_penjual');
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

        // Product Category
        $prodCat = ProductCategory::create([
            'name' => 'Makanan',
            'slug' => 'makanan',
            'is_active' => true,
        ]);

        // Product
        $this->product = Product::create([
            'store_id' => $store->id,
            'product_category_id' => $prodCat->id,
            'name' => 'Baju Keren',
            'slug' => 'baju-keren',
            'description' => 'Baju kaos FEB keren',
            'price' => 50000,
            'stock' => 10,
            'status' => 'active',
        ]);

        // Service Category
        $servCat = ServiceCategory::create([
            'name' => 'Jasa Desain',
            'slug' => 'jasa-desain',
            'is_active' => true,
        ]);

        // Service
        $this->service = Service::create([
            'store_id' => $store->id,
            'service_category_id' => $servCat->id,
            'name' => 'Desain Logo',
            'slug' => 'desain-logo',
            'description' => 'Desain logo professional',
            'price_from' => 100000,
            'lokasi_layanan' => 'Samarinda',
            'status' => 'active',
        ]);

        // Order
        $this->order = Order::create([
            'order_number' => 'ORD-TEST-REV',
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

        $this->orderItem = $this->order->items()->create([
            'product_id' => $this->product->id,
            'name' => $this->product->name,
            'price' => $this->product->price,
            'quantity' => 1,
        ]);
    }

    /**
     * Test buyer can write review for completed order.
     */
    public function test_buyer_can_create_review_for_completed_order_item()
    {
        // Set order to selesai
        $this->order->update(['status' => 'selesai']);

        $token = $this->buyer->createToken('auth_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->postJson('/api/reviews', [
            'order_item_id' => $this->orderItem->id,
            'reviewable_type' => 'product',
            'reviewable_id' => $this->product->id,
            'rating' => 5,
            'comment' => 'Produknya sangat keren!',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('reviews', [
            'order_item_id' => $this->orderItem->id,
            'rating' => 5,
            'comment' => 'Produknya sangat keren!',
        ]);
    }

    /**
     * Test buyer cannot review non-completed order.
     */
    public function test_buyer_cannot_create_review_for_non_completed_order()
    {
        $token = $this->buyer->createToken('auth_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->postJson('/api/reviews', [
            'order_item_id' => $this->orderItem->id,
            'reviewable_type' => 'product',
            'reviewable_id' => $this->product->id,
            'rating' => 4,
            'comment' => 'Belum selesai tapi mau ulas.',
        ]);

        $response->assertStatus(400);
        $response->assertJsonFragment([
            'message' => 'Ulasan hanya dapat dibuat setelah status pesanan selesai.',
        ]);
    }

    /**
     * Test buyer cannot review other user's order item.
     */
    public function test_buyer_cannot_create_review_for_other_users_order_item()
    {
        $this->order->update(['status' => 'selesai']);

        $token = $this->otherBuyer->createToken('auth_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->postJson('/api/reviews', [
            'order_item_id' => $this->orderItem->id,
            'reviewable_type' => 'product',
            'reviewable_id' => $this->product->id,
            'rating' => 5,
            'comment' => 'Bukan pesanan saya.',
        ]);

        $response->assertStatus(403);
    }

    /**
     * Test buyer cannot review twice on the same order item.
     */
    public function test_buyer_cannot_create_review_twice_for_same_order_item()
    {
        $this->order->update(['status' => 'selesai']);

        $token = $this->buyer->createToken('auth_token')->plainTextToken;

        // First review
        $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->postJson('/api/reviews', [
            'order_item_id' => $this->orderItem->id,
            'reviewable_type' => 'product',
            'reviewable_id' => $this->product->id,
            'rating' => 5,
            'comment' => 'Ulasan pertama.',
        ]);

        // Second review
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->postJson('/api/reviews', [
            'order_item_id' => $this->orderItem->id,
            'reviewable_type' => 'product',
            'reviewable_id' => $this->product->id,
            'rating' => 4,
            'comment' => 'Ulasan kedua.',
        ]);

        $response->assertStatus(400);
        $response->assertJsonFragment([
            'message' => 'Anda sudah mengulas item pesanan ini.',
        ]);
    }

    /**
     * Test validation bounds rating (1-5).
     */
    public function test_buyer_cannot_create_review_with_invalid_rating()
    {
        $this->order->update(['status' => 'selesai']);

        $token = $this->buyer->createToken('auth_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->postJson('/api/reviews', [
            'order_item_id' => $this->orderItem->id,
            'reviewable_type' => 'product',
            'reviewable_id' => $this->product->id,
            'rating' => 6, // Invalid rating
            'comment' => 'Rating kebesaran.',
        ]);

        $response->assertStatus(422);
    }

    /**
     * Test seller can reply to reviews of their store.
     */
    public function test_seller_can_reply_to_review()
    {
        $this->order->update(['status' => 'selesai']);

        // Create review manually
        $review = Review::create([
            'user_id' => $this->buyer->id,
            'order_item_id' => $this->orderItem->id,
            'store_id' => $this->product->store_id,
            'reviewable_type' => Product::class,
            'reviewable_id' => $this->product->id,
            'rating' => 5,
            'comment' => 'Bagus sekali!',
        ]);

        $token = $this->seller->createToken('auth_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->postJson("/api/reviews/{$review->id}/reply", [
            'reply' => 'Terima kasih banyak ulasannya!',
        ]);

        $response->assertStatus(200);
        $this->assertEquals('Terima kasih banyak ulasannya!', $review->fresh()->reply);
    }

    /**
     * Test seller cannot reply to reviews of other stores.
     */
    public function test_seller_cannot_reply_to_other_stores_review()
    {
        $this->order->update(['status' => 'selesai']);

        // Create review manually
        $review = Review::create([
            'user_id' => $this->buyer->id,
            'order_item_id' => $this->orderItem->id,
            'store_id' => $this->product->store_id,
            'reviewable_type' => Product::class,
            'reviewable_id' => $this->product->id,
            'rating' => 5,
            'comment' => 'Bagus sekali!',
        ]);

        $token = $this->otherSeller->createToken('auth_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->postJson("/api/reviews/{$review->id}/reply", [
            'reply' => 'Saya mau bajak tanggapan ini.',
        ]);

        $response->assertStatus(403);
    }

    /**
     * Test verified alumni can review service directly.
     */
    public function test_verified_alumni_can_review_service()
    {
        $token = $this->buyer->createToken('auth_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->postJson('/api/reviews', [
            'reviewable_type' => 'service',
            'reviewable_id' => $this->service->id,
            'rating' => 5,
            'comment' => 'Pelayanan jasa sangat baik!',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('reviews', [
            'reviewable_type' => Service::class,
            'reviewable_id' => $this->service->id,
            'rating' => 5,
            'comment' => 'Pelayanan jasa sangat baik!',
        ]);
    }

    /**
     * Test verified alumni cannot review service twice.
     */
    public function test_verified_alumni_cannot_review_service_twice()
    {
        $token = $this->buyer->createToken('auth_token')->plainTextToken;

        // First review
        $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->postJson('/api/reviews', [
            'reviewable_type' => 'service',
            'reviewable_id' => $this->service->id,
            'rating' => 5,
            'comment' => 'Pelayanan jasa sangat baik!',
        ]);

        // Second review
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->postJson('/api/reviews', [
            'reviewable_type' => 'service',
            'reviewable_id' => $this->service->id,
            'rating' => 4,
            'comment' => 'Ulasan kedua.',
        ]);

        $response->assertStatus(400);
        $response->assertJsonFragment([
            'message' => 'Anda sudah memberikan ulasan untuk jasa ini.',
        ]);
    }

    /**
     * Test average rating logic on models.
     */
    public function test_average_rating_and_count_calculation()
    {
        // 1. Product ratings
        Review::create([
            'user_id' => $this->buyer->id,
            'store_id' => $this->product->store_id,
            'reviewable_type' => Product::class,
            'reviewable_id' => $this->product->id,
            'rating' => 4,
            'comment' => 'Cukup bagus.',
        ]);

        Review::create([
            'user_id' => $this->otherBuyer->id,
            'store_id' => $this->product->store_id,
            'reviewable_type' => Product::class,
            'reviewable_id' => $this->product->id,
            'rating' => 5,
            'comment' => 'Sangat bagus!',
        ]);

        // 2. Service ratings
        Review::create([
            'user_id' => $this->buyer->id,
            'store_id' => $this->service->store_id,
            'reviewable_type' => Service::class,
            'reviewable_id' => $this->service->id,
            'rating' => 3,
            'comment' => 'Biasa saja.',
        ]);

        $productFresh = $this->product->fresh();
        $serviceFresh = $this->service->fresh();
        $storeFresh = $this->product->store->fresh();

        // Product average: (4+5)/2 = 4.5. Total = 2.
        $this->assertEquals(4.5, $productFresh->average_rating);
        $this->assertEquals(2, $productFresh->reviews_count);

        // Service average: 3. Total = 1.
        $this->assertEquals(3.0, $serviceFresh->average_rating);
        $this->assertEquals(1, $serviceFresh->reviews_count);

        // Store average: (4+5+3)/3 = 4.0. Total = 3.
        $this->assertEquals(4.0, $storeFresh->average_rating);
        $this->assertEquals(3, $storeFresh->reviews_count);
    }
}
