<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;

    protected $buyerUser;

    protected $unverifiedUser;

    protected $sellerUser;

    protected $otherSellerUser;

    protected $store;

    protected $otherStore;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        // Super Admin
        $this->adminUser = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
        ]);
        $this->adminUser->assignRole('super_admin');

        // Verified Buyer
        $this->buyerUser = User::create([
            'name' => 'Buyer User',
            'email' => 'buyer@example.com',
            'password' => bcrypt('password123'),
        ]);
        $this->buyerUser->assignRole('alumni_pembeli');
        $this->buyerUser->profile()->create([
            'nim' => '1801015111',
            'program_studi' => 'S1 Manajemen',
            'tahun_masuk' => 2018,
            'tahun_lulus' => 2022,
            'whatsapp' => '081234567899',
            'status_verifikasi' => 'verified',
            'badge_verified' => true,
        ]);

        // Unverified User
        $this->unverifiedUser = User::create([
            'name' => 'Unverified User',
            'email' => 'unverified@example.com',
            'password' => bcrypt('password123'),
        ]);
        $this->unverifiedUser->assignRole('alumni_pembeli');
        $this->unverifiedUser->profile()->create([
            'nim' => '1801015222',
            'program_studi' => 'S1 Manajemen',
            'tahun_masuk' => 2018,
            'tahun_lulus' => 2022,
            'whatsapp' => '081234567888',
            'status_verifikasi' => 'pending',
            'badge_verified' => false,
        ]);

        // Seller 1
        $this->sellerUser = User::create([
            'name' => 'Seller Satu',
            'email' => 'seller1@example.com',
            'password' => bcrypt('password123'),
        ]);
        $this->sellerUser->assignRole('alumni_pembeli');
        $profile1 = $this->sellerUser->profile()->create([
            'nim' => '1801015333',
            'program_studi' => 'S1 Akuntansi',
            'tahun_masuk' => 2018,
            'tahun_lulus' => 2022,
            'whatsapp' => '081234567890',
            'status_verifikasi' => 'verified',
            'badge_verified' => true,
        ]);
        $this->store = $profile1->store()->create([
            'name' => 'Toko S1',
            'kategori_usaha' => 'Fashion',
            'whatsapp' => '081234567890',
            'kota' => 'Samarinda',
            'status' => 'active',
            'tahun_berdiri' => 2024,
        ]);

        // Seller 2
        $this->otherSellerUser = User::create([
            'name' => 'Seller Dua',
            'email' => 'seller2@example.com',
            'password' => bcrypt('password123'),
        ]);
        $this->otherSellerUser->assignRole('alumni_pembeli');
        $profile2 = $this->otherSellerUser->profile()->create([
            'nim' => '1801015444',
            'program_studi' => 'S1 Akuntansi',
            'tahun_masuk' => 2018,
            'tahun_lulus' => 2022,
            'whatsapp' => '081234567891',
            'status_verifikasi' => 'verified',
            'badge_verified' => true,
        ]);
        $this->otherStore = $profile2->store()->create([
            'name' => 'Toko S2',
            'kategori_usaha' => 'Fashion',
            'whatsapp' => '081234567891',
            'kota' => 'Samarinda',
            'status' => 'active',
            'tahun_berdiri' => 2024,
        ]);
    }

    /**
     * Test regular buyer cannot access admin store moderation routes.
     */
    public function test_buyer_blocked_from_admin_store_moderation()
    {
        $token = $this->buyerUser->createToken('auth_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->getJson('/api/admin/stores');

        $response->assertStatus(403);
    }

    /**
     * Test regular buyer cannot access admin category creation routes.
     */
    public function test_buyer_blocked_from_admin_category_management()
    {
        $token = $this->buyerUser->createToken('auth_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->postJson('/api/admin/product-categories', [
            'name' => 'New Category',
        ]);

        $response->assertStatus(403);
    }

    /**
     * Test unverified user cannot request/register a store.
     */
    public function test_unverified_user_cannot_register_store()
    {
        $token = $this->unverifiedUser->createToken('auth_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->postJson('/api/stores', [
            'name' => 'Unverified Store',
            'kategori_usaha' => 'Fashion',
            'whatsapp' => '081234567890',
            'kota' => 'Samarinda',
            'tahun_berdiri' => 2025,
        ]);

        $response->assertStatus(403);
    }

    /**
     * Test seller 1 cannot modify seller 2's store settings.
     */
    public function test_seller_cannot_modify_other_store_settings()
    {
        $token = $this->sellerUser->createToken('auth_token')->plainTextToken;

        // Try to update store info. Since the endpoint is parameterless and implicitly resolves to the
        // authenticated user's store, they can only update their own store ($this->store).
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->putJson('/api/stores/my-store', [
            'name' => 'Updated Store S1',
            'kategori_usaha' => 'Fashion',
            'whatsapp' => '081234567890',
            'kota' => 'Samarinda',
            'tahun_berdiri' => 2024,
            'delivery_type' => 'fixed',
            'fixed_delivery_fee' => 1000,
        ]);

        $response->assertStatus(200);

        // Assert that Seller 1's store is updated
        $this->assertEquals('Updated Store S1', $this->store->fresh()->name);

        // Assert that Seller 2's store ($this->otherStore) is NOT updated
        $this->assertEquals('Toko S2', $this->otherStore->fresh()->name);
    }
}
