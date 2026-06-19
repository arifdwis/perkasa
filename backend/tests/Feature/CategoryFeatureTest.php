<?php

namespace Tests\Feature;

use App\Models\ProductCategory;
use App\Models\ServiceCategory;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        // 1. Create Admin
        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
        ]);
        $this->admin->assignRole('super_admin');

        // 2. Create Regular User
        $this->user = User::create([
            'name' => 'Regular Alumni',
            'email' => 'alumni@example.com',
            'password' => bcrypt('password123'),
        ]);
        $this->user->assignRole('alumni_pembeli');
    }

    /**
     * Test public can retrieve active product categories.
     */
    public function test_public_can_list_active_product_categories()
    {
        ProductCategory::create(['name' => 'Active Category', 'slug' => 'active-category', 'is_active' => true]);
        ProductCategory::create(['name' => 'Inactive Category', 'slug' => 'inactive-category', 'is_active' => false]);

        $response = $this->getJson('/api/product-categories');

        $response->assertStatus(200);
        $response->assertJsonCount(1);
        $response->assertJsonPath('0.name', 'Active Category');
    }

    /**
     * Test regular user cannot create category.
     */
    public function test_regular_user_blocked_from_creating_category()
    {
        $token = $this->user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->postJson('/api/admin/product-categories', [
            'name' => 'Forbidden Category',
        ]);

        $response->assertStatus(403);
    }

    /**
     * Test admin can perform CRUD on product categories.
     */
    public function test_admin_can_crud_product_categories()
    {
        $token = $this->admin->createToken('auth_token')->plainTextToken;

        // 1. Create
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->postJson('/api/admin/product-categories', [
            'name' => 'New Tech Category',
            'is_active' => true,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('product_categories', ['name' => 'New Tech Category']);

        $category = ProductCategory::where('name', 'New Tech Category')->first();

        // 2. Update
        $updateResponse = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->putJson("/api/admin/product-categories/{$category->id}", [
            'name' => 'Updated Tech Category',
            'is_active' => false,
        ]);

        $updateResponse->assertStatus(200);
        $this->assertDatabaseHas('product_categories', ['name' => 'Updated Tech Category', 'is_active' => false]);

        // 3. Delete
        $deleteResponse = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->deleteJson("/api/admin/product-categories/{$category->id}");

        $deleteResponse->assertStatus(200);
        $this->assertDatabaseMissing('product_categories', ['name' => 'Updated Tech Category']);
    }

    /**
     * Test admin can perform CRUD on service categories.
     */
    public function test_admin_can_crud_service_categories()
    {
        $token = $this->admin->createToken('auth_token')->plainTextToken;

        // 1. Create
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->postJson('/api/admin/service-categories', [
            'name' => 'Legal Advisory',
            'is_active' => true,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('service_categories', ['name' => 'Legal Advisory']);

        $category = ServiceCategory::where('name', 'Legal Advisory')->first();

        // 2. Update
        $updateResponse = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->putJson("/api/admin/service-categories/{$category->id}", [
            'name' => 'Legal Advisory Updated',
            'is_active' => true,
        ]);

        $updateResponse->assertStatus(200);

        // 3. Delete
        $deleteResponse = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->deleteJson("/api/admin/service-categories/{$category->id}");

        $deleteResponse->assertStatus(200);
        $this->assertDatabaseMissing('service_categories', ['name' => 'Legal Advisory Updated']);
    }
}
