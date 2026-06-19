<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;

    protected $regularUser;

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

        // Regular User
        $this->regularUser = User::create([
            'name' => 'Regular User',
            'email' => 'regular@example.com',
            'password' => bcrypt('password123'),
        ]);
        $this->regularUser->assignRole('alumni_pembeli');
    }

    /**
     * Test admin can view roles and permissions list.
     */
    public function test_admin_can_view_roles_and_permissions()
    {
        $token = $this->adminUser->createToken('auth_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->getJson('/api/admin/roles');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            ['id', 'name', 'permissions'],
        ]);

        $permissionsResponse = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->getJson('/api/admin/permissions');

        $permissionsResponse->assertStatus(200);
        $permissionsResponse->assertJsonStructure([
            ['id', 'name'],
        ]);
    }

    /**
     * Test assigning a role to a user updates permissions effectively.
     */
    public function test_assign_role_to_user()
    {
        $token = $this->adminUser->createToken('auth_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->postJson("/api/admin/users/{$this->regularUser->id}/assign-role", [
            'roles' => ['admin_marketplace'],
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'message',
            'user',
        ]);

        $this->assertTrue($this->regularUser->fresh()->hasRole('admin_marketplace'));
    }

    /**
     * Test creating a custom role with custom permissions dynamically.
     */
    public function test_create_custom_role_with_permissions()
    {
        $token = $this->adminUser->createToken('auth_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->postJson('/api/admin/roles', [
            'name' => 'moderator_toko',
            'permissions' => ['verify_store'],
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('roles', [
            'name' => 'moderator_toko',
        ]);

        $role = Role::findByName('moderator_toko', 'web');
        $this->assertTrue($role->hasPermissionTo('verify_store'));
    }
}
