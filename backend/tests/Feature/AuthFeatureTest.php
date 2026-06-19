<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed initial roles and permissions
        $this->seed(RolePermissionSeeder::class);
    }

    /**
     * Test successful user registration.
     */
    public function test_user_can_register_as_alumni()
    {
        $response = $this->postJson('/api/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'nim' => '1801015999',
            'program_studi' => 'S1 Manajemen',
            'tahun_masuk' => 2018,
            'tahun_lulus' => 2022,
            'whatsapp' => '081234567899',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'message',
            'user' => [
                'id',
                'name',
                'email',
                'profile' => [
                    'id',
                    'nim',
                    'program_studi',
                    'status_verifikasi',
                ],
            ],
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
        ]);

        $this->assertDatabaseHas('alumni_profiles', [
            'nim' => '1801015999',
            'status_verifikasi' => 'pending',
        ]);

        // Assert role assignment
        $user = User::where('email', 'john@example.com')->first();
        $this->assertTrue($user->hasRole('alumni_pembeli'));
    }

    /**
     * Test successful login.
     */
    public function test_user_can_login_with_correct_credentials()
    {
        $user = User::create([
            'name' => 'Alice Smith',
            'email' => 'alice@example.com',
            'password' => Hash::make('password123'),
        ]);
        $user->assignRole('alumni_pembeli');
        $user->profile()->create([
            'nim' => '1801015888',
            'program_studi' => 'S1 Akuntansi',
            'tahun_masuk' => 2018,
            'tahun_lulus' => 2022,
            'whatsapp' => '081234567888',
            'status_verifikasi' => 'verified',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'alice@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'access_token',
            'token_type',
            'user',
            'permissions',
        ]);
    }

    /**
     * Test wrong password returns 401.
     */
    public function test_user_cannot_login_with_incorrect_password()
    {
        $user = User::create([
            'name' => 'Alice Smith',
            'email' => 'alice@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'alice@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test suspended user is blocked.
     */
    public function test_suspended_user_cannot_login()
    {
        $user = User::create([
            'name' => 'Suspended User',
            'email' => 'suspended@example.com',
            'password' => Hash::make('password123'),
        ]);
        $user->assignRole('alumni_pembeli');
        $user->profile()->create([
            'nim' => '1801015777',
            'program_studi' => 'S1 Akuntansi',
            'tahun_masuk' => 2018,
            'tahun_lulus' => 2022,
            'whatsapp' => '081234567777',
            'status_verifikasi' => 'suspended',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'suspended@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Akses diblokir. Akun alumni Anda ditangguhkan (suspended). Hubungi admin.',
        ]);
    }

    /**
     * Test access to protected 'me' endpoint.
     */
    public function test_authenticated_user_can_access_me_endpoint()
    {
        $user = User::create([
            'name' => 'Alice Smith',
            'email' => 'alice@example.com',
            'password' => Hash::make('password123'),
        ]);
        $user->assignRole('alumni_pembeli');
        $user->profile()->create([
            'nim' => '1801015888',
            'program_studi' => 'S1 Akuntansi',
            'tahun_masuk' => 2018,
            'tahun_lulus' => 2022,
            'whatsapp' => '081234567888',
            'status_verifikasi' => 'verified',
        ]);

        $token = $user->createToken('test_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->getJson('/api/me');

        $response->assertStatus(200);
        $response->assertJsonPath('user.email', 'alice@example.com');
    }

    /**
     * Test successful logout.
     */
    public function test_user_can_logout()
    {
        $user = User::create([
            'name' => 'Alice Smith',
            'email' => 'alice@example.com',
            'password' => Hash::make('password123'),
        ]);
        $token = $user->createToken('test_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->postJson('/api/logout');

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Berhasil keluar log (logout).',
        ]);
    }

    /**
     * Test email verification endpoint.
     */
    public function test_user_can_verify_email()
    {
        $user = User::create([
            'name' => 'John Ver',
            'email' => 'johnver@example.com',
            'password' => Hash::make('password123'),
        ]);

        $hash = sha1($user->getEmailForVerification());

        $response = $this->getJson("/api/email/verify/{$user->id}/{$hash}");

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Email Anda berhasil diverifikasi.',
        ]);

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    /**
     * Test non-admin is blocked from admin routes.
     */
    public function test_non_admin_blocked_from_admin_routes()
    {
        $user = User::create([
            'name' => 'Regular Alumni',
            'email' => 'regular@example.com',
            'password' => Hash::make('password123'),
        ]);
        $user->assignRole('alumni_pembeli');

        $token = $user->createToken('test_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->getJson('/api/admin/roles');

        $response->assertStatus(403);
    }

    /**
     * Test admin can perform CRUD on roles.
     */
    public function test_admin_can_perform_role_crud()
    {
        $admin = User::create([
            'name' => 'Super Admin User',
            'email' => 'superadmin@example.com',
            'password' => Hash::make('password123'),
        ]);
        $admin->assignRole('super_admin');

        $token = $admin->createToken('test_token')->plainTextToken;

        // 1. Create Role
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->postJson('/api/admin/roles', [
            'name' => 'custom_manager',
            'permissions' => ['verify_alumni', 'verify_store'],
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('roles', ['name' => 'custom_manager']);

        // Get Role ID
        $role = Role::findByName('custom_manager', 'web');

        // 2. Update Role Permissions
        $updateResponse = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->putJson("/api/admin/roles/{$role->id}", [
            'name' => 'custom_manager_updated',
            'permissions' => ['verify_alumni'],
        ]);

        $updateResponse->assertStatus(200);
        $this->assertDatabaseHas('roles', ['name' => 'custom_manager_updated']);

        // 3. Delete Role
        $deleteResponse = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->deleteJson("/api/admin/roles/{$role->id}");

        $deleteResponse->assertStatus(200);
        $this->assertDatabaseMissing('roles', ['name' => 'custom_manager_updated']);
    }

    /**
     * Test Super Admin role is protected from deletion.
     */
    public function test_super_admin_role_cannot_be_deleted()
    {
        $admin = User::create([
            'name' => 'Super Admin User',
            'email' => 'superadmin@example.com',
            'password' => Hash::make('password123'),
        ]);
        $admin->assignRole('super_admin');

        $token = $admin->createToken('test_token')->plainTextToken;
        $superAdminRole = Role::findByName('super_admin', 'web');

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->deleteJson("/api/admin/roles/{$superAdminRole->id}");

        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Role Super Admin dilindungi dan tidak dapat dihapus.',
        ]);
    }

    /**
     * Test login rate limiting blocks after 5 requests.
     */
    public function test_login_rate_limiting_blocks_excessive_requests()
    {
        // We will make 5 attempts, which should return auth errors (401)
        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson('/api/login', [
                'email' => 'ratelimit@example.com',
                'password' => 'wrongpassword',
            ]);
            $response->assertStatus(401);
        }

        // The 6th attempt should return 429 Too Many Requests
        $response = $this->postJson('/api/login', [
            'email' => 'ratelimit@example.com',
            'password' => 'wrongpassword',
        ]);
        $response->assertStatus(429);
    }
}
