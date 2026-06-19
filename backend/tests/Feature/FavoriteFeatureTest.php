<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected $buyerUser;

    protected $unverifiedUser;

    protected $sellerStore;

    protected $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        // 1. Create Verified Buyer User
        $this->buyerUser = User::create([
            'name' => 'Buyer Alumni',
            'email' => 'buyer@example.com',
            'password' => bcrypt('password123'),
        ]);
        $this->buyerUser->assignRole('alumni_pembeli');
        $this->buyerUser->profile()->create([
            'nim' => '1801015111',
            'program_studi' => 'S1 Manajemen',
            'tahun_masuk' => 2018,
            'tahun_lulus' => 2022,
            'whatsapp' => '081234567811',
            'status_verifikasi' => 'verified',
            'badge_verified' => true,
        ]);

        // 2. Create Unverified User
        $this->unverifiedUser = User::create([
            'name' => 'Unverified User',
            'email' => 'unverified@example.com',
            'password' => bcrypt('password123'),
        ]);
        $this->unverifiedUser->assignRole('alumni_pembeli');
        $this->unverifiedUser->profile()->create([
            'nim' => '1801015222',
            'program_studi' => 'S1 Akuntansi',
            'tahun_masuk' => 2018,
            'tahun_lulus' => 2022,
            'whatsapp' => '081234567822',
            'status_verifikasi' => 'pending',
            'badge_verified' => false,
        ]);

        // 3. Create Store & Product to favorite
        $seller = User::create([
            'name' => 'Seller',
            'email' => 'seller@example.com',
            'password' => bcrypt('password123'),
        ]);
        $sellerProfile = $seller->profile()->create([
            'nim' => '1801015333',
            'program_studi' => 'S1 Manajemen',
            'tahun_masuk' => 2018,
            'tahun_lulus' => 2022,
            'whatsapp' => '081234567833',
            'status_verifikasi' => 'verified',
            'badge_verified' => true,
        ]);
        $this->sellerStore = $sellerProfile->store()->create([
            'name' => 'Toko Kopi',
            'kategori_usaha' => 'Makanan dan Minuman',
            'whatsapp' => '081234567833',
            'kota' => 'Samarinda',
            'tahun_berdiri' => 2025,
            'status' => 'active',
            'delivery_type' => 'fixed',
        ]);

        $cat = ProductCategory::create(['name' => 'Kopi', 'slug' => 'kopi']);

        $this->product = Product::create([
            'store_id' => $this->sellerStore->id,
            'product_category_id' => $cat->id,
            'name' => 'Espresso',
            'slug' => 'espresso',
            'description' => 'Espresso kental',
            'price' => 12000,
            'stock' => 10,
            'status' => 'active',
        ]);
    }

    /**
     * Test verified user can toggle favorite on a product.
     */
    public function test_verified_user_can_toggle_favorite()
    {
        $token = $this->buyerUser->createToken('auth_token')->plainTextToken;

        // 1. ADD favorite
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->postJson('/api/favorites/toggle', [
            'favoritable_id' => $this->product->id,
            'favoritable_type' => 'product',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('favorited', true);
        $this->assertDatabaseHas('favorites', [
            'user_id' => $this->buyerUser->id,
            'favoritable_id' => $this->product->id,
        ]);

        // 2. LIST favorites
        $listResponse = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->getJson('/api/favorites');

        $listResponse->assertStatus(200);
        $listResponse->assertJsonCount(1, 'products');

        // 3. REMOVE favorite (toggle again)
        $toggleOffResponse = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->postJson('/api/favorites/toggle', [
            'favoritable_id' => $this->product->id,
            'favoritable_type' => 'product',
        ]);

        $toggleOffResponse->assertStatus(200);
        $toggleOffResponse->assertJsonPath('favorited', false);
        $this->assertDatabaseMissing('favorites', [
            'user_id' => $this->buyerUser->id,
            'favoritable_id' => $this->product->id,
        ]);
    }

    /**
     * Test unverified user cannot access favorites.
     */
    public function test_unverified_user_blocked_from_favorites()
    {
        $token = $this->unverifiedUser->createToken('auth_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->postJson('/api/favorites/toggle', [
            'favoritable_id' => $this->product->id,
            'favoritable_type' => 'product',
        ]);

        $response->assertStatus(403);
    }
}
