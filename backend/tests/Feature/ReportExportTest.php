<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportExportTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;

    protected $buyerUser;

    protected $sellerUser;

    protected $store;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        // Admin User
        $this->adminUser = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
        ]);
        $this->adminUser->assignRole('super_admin');

        // Seller User
        $this->sellerUser = User::create([
            'name' => 'Seller User',
            'email' => 'seller@example.com',
            'password' => bcrypt('password123'),
        ]);
        $this->sellerUser->assignRole('alumni_pembeli');
        $profile = $this->sellerUser->profile()->create([
            'nim' => '1801015123',
            'program_studi' => 'S1 Manajemen',
            'tahun_masuk' => 2018,
            'tahun_lulus' => 2022,
            'whatsapp' => '081234567899',
            'status_verifikasi' => 'verified',
            'badge_verified' => true,
        ]);
        $this->store = $profile->store()->create([
            'name' => 'Toko Keren',
            'kategori_usaha' => 'Fashion',
            'whatsapp' => '081234567899',
            'kota' => 'Samarinda',
            'status' => 'active',
            'delivery_type' => 'fixed',
            'fixed_delivery_fee' => 10000,
            'tahun_berdiri' => 2024,
        ]);

        // Buyer User
        $this->buyerUser = User::create([
            'name' => 'Buyer User',
            'email' => 'buyer@example.com',
            'password' => bcrypt('password123'),
        ]);
        $this->buyerUser->assignRole('alumni_pembeli');
        $this->buyerUser->profile()->create([
            'nim' => '1801015321',
            'program_studi' => 'S1 Akuntansi',
            'tahun_masuk' => 2018,
            'tahun_lulus' => 2022,
            'whatsapp' => '081234567811',
            'status_verifikasi' => 'verified',
            'badge_verified' => true,
        ]);

        // Categories
        $pCategory = ProductCategory::create([
            'name' => 'Makanan',
            'slug' => 'makanan',
            'is_active' => true,
        ]);

        // Create mock product
        Product::create([
            'store_id' => $this->store->id,
            'product_category_id' => $pCategory->id,
            'name' => 'Nasi Goreng',
            'slug' => 'nasi-goreng',
            'description' => 'Nasi goreng lezat khas alumni',
            'price' => 15000,
            'stock' => 10,
            'status' => 'active',
        ]);

        // Create order
        Order::create([
            'user_id' => $this->buyerUser->id,
            'store_id' => $this->store->id,
            'order_number' => 'ORD-123456',
            'nama_penerima' => 'Buyer',
            'telepon_penerima' => '081234567811',
            'alamat_penerima' => 'Samarinda',
            'subtotal' => 15000,
            'biaya_antar' => 10000,
            'total' => 25000,
            'status' => 'selesai',
            'payment_method' => 'cod',
        ]);
    }

    /**
     * Test regular buyer cannot access reports.
     */
    public function test_buyer_blocked_from_reports()
    {
        $token = $this->buyerUser->createToken('auth_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->getJson('/api/admin/reports/alumni/export');

        $response->assertStatus(403);
    }

    /**
     * Test admin can export alumni report.
     */
    public function test_admin_can_export_alumni()
    {
        $token = $this->adminUser->createToken('auth_token')->plainTextToken;

        // Excel
        $responseExcel = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->get('/api/admin/reports/alumni/export?format=excel');
        $responseExcel->assertStatus(200);
        $responseExcel->assertHeader('Content-Disposition');

        // CSV
        $responseCsv = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->get('/api/admin/reports/alumni/export?format=csv');
        $responseCsv->assertStatus(200);
        $responseCsv->assertHeader('Content-Disposition');

        // PDF
        $responsePdf = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->get('/api/admin/reports/alumni/export?format=pdf');
        $responsePdf->assertStatus(200);
        $responsePdf->assertHeader('Content-Disposition');
    }

    /**
     * Test admin can export stores report.
     */
    public function test_admin_can_export_stores()
    {
        $token = $this->adminUser->createToken('auth_token')->plainTextToken;

        // Excel
        $responseExcel = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->get('/api/admin/reports/stores/export?format=excel');
        $responseExcel->assertStatus(200);

        // PDF
        $responsePdf = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->get('/api/admin/reports/stores/export?format=pdf');
        $responsePdf->assertStatus(200);
    }

    /**
     * Test admin can export products report.
     */
    public function test_admin_can_export_products()
    {
        $token = $this->adminUser->createToken('auth_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->get('/api/admin/reports/products/export?format=excel');
        $response->assertStatus(200);
    }

    /**
     * Test admin can export orders report.
     */
    public function test_admin_can_export_orders()
    {
        $token = $this->adminUser->createToken('auth_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->get('/api/admin/reports/orders/export?format=excel');
        $response->assertStatus(200);
    }

    /**
     * Test admin can export sales report.
     */
    public function test_admin_can_export_sales()
    {
        $token = $this->adminUser->createToken('auth_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->get('/api/admin/reports/sales/export?format=excel');
        $response->assertStatus(200);
    }
}
