<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
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
                ]
            ]
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com'
        ]);

        $this->assertDatabaseHas('alumni_profiles', [
            'nim' => '1801015999',
            'status_verifikasi' => 'pending'
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
            'permissions'
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
            'message' => 'Akses diblokir. Akun alumni Anda ditangguhkan (suspended). Hubungi admin.'
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
            'message' => 'Berhasil keluar log (logout).'
        ]);
    }
}
