<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StoreFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected $verifiedAlumni;

    protected $unverifiedAlumni;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed Spatie roles & permissions
        $this->seed(RolePermissionSeeder::class);

        // 1. Create Admin
        $this->admin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
        ]);
        $this->admin->assignRole('super_admin');

        // 2. Create Verified Alumni
        $this->verifiedAlumni = User::create([
            'name' => 'Verified Alumni',
            'email' => 'verified@example.com',
            'password' => bcrypt('password123'),
        ]);
        $this->verifiedAlumni->assignRole('alumni_pembeli');
        $this->verifiedAlumni->profile()->create([
            'nim' => '1801015111',
            'program_studi' => 'S1 Manajemen',
            'tahun_masuk' => 2018,
            'tahun_lulus' => 2022,
            'whatsapp' => '081234567811',
            'status_verifikasi' => 'verified',
            'badge_verified' => true,
        ]);

        // 3. Create Unverified Alumni
        $this->unverifiedAlumni = User::create([
            'name' => 'Unverified Alumni',
            'email' => 'unverified@example.com',
            'password' => bcrypt('password123'),
        ]);
        $this->unverifiedAlumni->assignRole('alumni_pembeli');
        $this->unverifiedAlumni->profile()->create([
            'nim' => '1801015222',
            'program_studi' => 'S1 Akuntansi',
            'tahun_masuk' => 2018,
            'tahun_lulus' => 2022,
            'whatsapp' => '081234567822',
            'status_verifikasi' => 'pending',
            'badge_verified' => false,
        ]);
    }

    /**
     * Test unverified alumni cannot register a store.
     */
    public function test_unverified_alumni_blocked_from_store_registration()
    {
        $token = $this->unverifiedAlumni->createToken('auth_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->postJson('/api/stores', [
            'name' => 'Toko Buku Unverified',
            'kategori_usaha' => 'Buku',
            'whatsapp' => '081234567822',
            'kota' => 'Samarinda',
            'tahun_berdiri' => 2024,
            'delivery_type' => 'fixed',
            'fixed_delivery_fee' => 10000,
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Akses ditolak. Akun alumni Anda belum diverifikasi oleh admin.', // Handled by EnsureAlumniIsVerified middleware
        ]);
    }

    /**
     * Test verified alumni can register a store.
     */
    public function test_verified_alumni_can_register_store()
    {
        $token = $this->verifiedAlumni->createToken('auth_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->postJson('/api/stores', [
            'name' => 'Toko Serba Ada',
            'description' => 'Toko alumni FEB termurah',
            'kategori_usaha' => 'UMKM',
            'whatsapp' => '081234567811',
            'kota' => 'Samarinda',
            'tahun_berdiri' => 2025,
            'delivery_type' => 'fixed',
            'fixed_delivery_fee' => 15000,
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure(['message', 'store']);

        $this->assertDatabaseHas('stores', [
            'name' => 'Toko Serba Ada',
            'status' => 'pending', // Defaults to pending
        ]);
    }

    /**
     * Test alumni cannot register more than one store.
     */
    public function test_alumni_restricted_to_one_store()
    {
        $token = $this->verifiedAlumni->createToken('auth_token')->plainTextToken;

        // Create first store
        $this->verifiedAlumni->profile->store()->create([
            'name' => 'Toko Pertama',
            'kategori_usaha' => 'UMKM',
            'whatsapp' => '081234567811',
            'kota' => 'Samarinda',
            'tahun_berdiri' => 2025,
            'status' => 'pending',
            'delivery_type' => 'fixed',
        ]);

        // Attempt second store
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->postJson('/api/stores', [
            'name' => 'Toko Kedua',
            'kategori_usaha' => 'Pakaian',
            'whatsapp' => '081234567811',
            'kota' => 'Samarinda',
            'tahun_berdiri' => 2026,
            'delivery_type' => 'fixed',
            'fixed_delivery_fee' => 5000,
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'message' => 'Alumni hanya diperbolehkan memiliki satu toko.',
        ]);
    }

    /**
     * Test admin can list and approve a store, granting owner the alumni_penjual role.
     */
    public function test_admin_can_approve_store_and_sync_roles()
    {
        $adminToken = $this->admin->createToken('auth_token')->plainTextToken;

        // Register store for verified alumni
        $store = $this->verifiedAlumni->profile->store()->create([
            'name' => 'Toko Kue Enak',
            'kategori_usaha' => 'Makanan dan Minuman',
            'whatsapp' => '081234567811',
            'kota' => 'Samarinda',
            'tahun_berdiri' => 2025,
            'status' => 'pending',
            'delivery_type' => 'fixed',
        ]);

        // Approve store
        $response = $this->withHeaders([
            'Authorization' => "Bearer $adminToken",
        ])->postJson("/api/admin/stores/{$store->id}/verify", [
            'action' => 'approve',
            'reason' => 'Data usaha valid',
        ]);

        $response->assertStatus(200);
        $this->assertEquals('active', $store->fresh()->status);

        // Assert role update
        $this->assertTrue($this->verifiedAlumni->fresh()->hasRole('alumni_penjual'));
    }

    /**
     * Test owner can update their store delivery fees per wilayah.
     */
    public function test_owner_can_sync_delivery_fees_per_wilayah()
    {
        $token = $this->verifiedAlumni->createToken('auth_token')->plainTextToken;

        $store = $this->verifiedAlumni->profile->store()->create([
            'name' => 'Toko Kue Enak',
            'kategori_usaha' => 'Makanan dan Minuman',
            'whatsapp' => '081234567811',
            'kota' => 'Samarinda',
            'tahun_berdiri' => 2025,
            'status' => 'active',
            'delivery_type' => 'fixed',
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->putJson('/api/stores/my-store', [
            'name' => 'Toko Kue Enak Terbaru',
            'kategori_usaha' => 'Makanan dan Minuman',
            'whatsapp' => '081234567811',
            'kota' => 'Samarinda',
            'tahun_berdiri' => 2025,
            'delivery_type' => 'per_wilayah',
            'delivery_fees' => [
                ['wilayah' => 'Samarinda Kota', 'fee' => 10000],
                ['wilayah' => 'Samarinda Ulu', 'fee' => 15000],
            ],
        ]);

        $response->assertStatus(200);
        $this->assertEquals('per_wilayah', $store->fresh()->delivery_type);
        $this->assertCount(2, $store->fresh()->deliveryFees);
        $this->assertDatabaseHas('store_delivery_fees', [
            'store_id' => $store->id,
            'wilayah' => 'Samarinda Kota',
            'fee' => 10000.00,
        ]);
    }
}
