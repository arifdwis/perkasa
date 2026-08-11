<?php

namespace Tests\Feature;

use App\Models\AlumniProfile;
use App\Models\KoperasiMember;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class KoperasiRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    /**
     * Valid step 1 payload.
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'whatsapp' => '081234567890',
            'nim' => '1801015001',
            'program_studi' => 'S1 Manajemen',
            'tahun_masuk' => 2018,
            'tahun_lulus' => 2022,
        ], $overrides);
    }

    /**
     * Create a registration already sitting in the staging table.
     */
    private function existingMember(array $overrides = []): KoperasiMember
    {
        return KoperasiMember::create(array_merge($this->payload(), ['status' => 'pending'], $overrides));
    }

    // ---------------------------------------------------------------- step 1

    public function test_step_one_stores_registration_without_credentials()
    {
        $response = $this->postJson('/api/koperasi/register', $this->payload());

        $response->assertStatus(201);

        // No token is handed out here — activation is a separate, self-started step.
        $this->assertArrayNotHasKey('token', $response->json());

        $this->assertDatabaseHas('koperasi_members', [
            'nim' => '1801015001',
            'email' => 'budi@example.com',
            'status' => 'pending',
            'user_id' => null,
        ]);
    }

    public function test_step_one_rejects_nim_already_registered_as_alumni()
    {
        $user = User::create([
            'name' => 'Alumni Lama',
            'email' => 'lama@example.com',
            'password' => Hash::make('password123'),
        ]);
        AlumniProfile::create([
            'user_id' => $user->id,
            'nim' => '1801015001',
            'program_studi' => 'S1 Akuntansi',
            'tahun_masuk' => 2015,
            'tahun_lulus' => 2019,
            'whatsapp' => '081200000000',
            'status_verifikasi' => 'verified',
        ]);

        $this->postJson('/api/koperasi/register', $this->payload())
            ->assertStatus(422)
            ->assertJsonValidationErrors('nim');
    }

    public function test_step_one_rejects_email_already_used_by_an_account()
    {
        User::create([
            'name' => 'Alumni Lama',
            'email' => 'budi@example.com',
            'password' => Hash::make('password123'),
        ]);

        $this->postJson('/api/koperasi/register', $this->payload())
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_step_one_rejects_duplicate_nim_in_staging_table()
    {
        $this->existingMember();

        $this->postJson('/api/koperasi/register', $this->payload(['email' => 'lain@example.com']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('nim');
    }

    public function test_step_one_rejects_graduation_year_before_entry_year()
    {
        $this->postJson('/api/koperasi/register', $this->payload(['tahun_lulus' => 2017]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('tahun_lulus');
    }

    // ---------------------------------------------------------------- step 2

    public function test_step_two_finds_registration_by_nim_and_name()
    {
        $this->existingMember();

        $response = $this->postJson('/api/koperasi/cek', [
            'nim' => '1801015001',
            'name' => 'budi santoso', // case-insensitive
        ]);

        $response->assertStatus(200)
            ->assertJson(['found' => true, 'nim' => '1801015001'])
            ->assertJsonStructure(['found', 'token', 'name', 'nim', 'email']);
    }

    public function test_step_two_rejects_when_name_does_not_match()
    {
        $this->existingMember();

        $this->postJson('/api/koperasi/cek', [
            'nim' => '1801015001',
            'name' => 'Orang Lain',
        ])->assertStatus(404)->assertJson(['found' => false]);
    }

    public function test_step_two_rejects_already_activated_registration()
    {
        $user = User::create([
            'name' => 'Budi Santoso',
            'username' => 'budi',
            'email' => 'budi@example.com',
            'password' => Hash::make('password123'),
        ]);
        $this->existingMember(['user_id' => $user->id]);

        // Same body and same message as "never registered" — no membership probing.
        $this->postJson('/api/koperasi/cek', [
            'nim' => '1801015001',
            'name' => 'Budi Santoso',
        ])->assertStatus(404)->assertJson(['found' => false]);
    }

    public function test_step_two_is_rate_limited()
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/koperasi/cek', ['nim' => '9999999999', 'name' => 'Tidak Ada'])
                ->assertStatus(404);
        }

        $this->postJson('/api/koperasi/cek', ['nim' => '9999999999', 'name' => 'Tidak Ada'])
            ->assertStatus(429);
    }

    // ---------------------------------------------------------------- step 3

    /**
     * Run steps 1 and 2, returning the activation token.
     */
    private function tokenFor(KoperasiMember $member): string
    {
        return $this->postJson('/api/koperasi/cek', [
            'nim' => $member->nim,
            'name' => $member->name,
        ])->json('token');
    }

    public function test_step_three_creates_user_and_alumni_profile()
    {
        $member = $this->existingMember();
        $token = $this->tokenFor($member);

        $this->postJson('/api/koperasi/buat-akun', [
            'token' => $token,
            'username' => 'budi_santoso',
            'password' => 'RahasiaKu123',
            'password_confirmation' => 'RahasiaKu123',
        ])->assertStatus(201);

        $this->assertDatabaseHas('users', [
            'username' => 'budi_santoso',
            'email' => 'budi@example.com',
            'name' => 'Budi Santoso',
        ]);

        $user = User::where('username', 'budi_santoso')->first();
        $this->assertTrue($user->hasRole('alumni_pembeli'));

        // Profile data copied verbatim from the staging row.
        $this->assertDatabaseHas('alumni_profiles', [
            'user_id' => $user->id,
            'nim' => '1801015001',
            'program_studi' => 'S1 Manajemen',
            'tahun_masuk' => 2018,
            'tahun_lulus' => 2022,
            'whatsapp' => '081234567890',
            'status_verifikasi' => 'pending',
            'is_koperasi_member' => true,
            'status_koperasi' => 'pending',
        ]);

        $this->assertDatabaseHas('koperasi_members', [
            'id' => $member->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_step_three_rejects_duplicate_username()
    {
        User::create([
            'name' => 'Orang Lain',
            'username' => 'budi_santoso',
            'email' => 'orang@example.com',
            'password' => Hash::make('password123'),
        ]);

        $token = $this->tokenFor($this->existingMember());

        $this->postJson('/api/koperasi/buat-akun', [
            'token' => $token,
            'username' => 'budi_santoso',
            'password' => 'RahasiaKu123',
            'password_confirmation' => 'RahasiaKu123',
        ])->assertStatus(422)->assertJsonValidationErrors('username');
    }

    public function test_step_three_rejects_invalid_username_format()
    {
        $token = $this->tokenFor($this->existingMember());

        $this->postJson('/api/koperasi/buat-akun', [
            'token' => $token,
            'username' => 'budi santoso!',
            'password' => 'RahasiaKu123',
            'password_confirmation' => 'RahasiaKu123',
        ])->assertStatus(422)->assertJsonValidationErrors('username');
    }

    public function test_step_three_rejects_unknown_token()
    {
        $this->existingMember();

        $this->postJson('/api/koperasi/buat-akun', [
            'token' => str_repeat('a', 64),
            'username' => 'budi_santoso',
            'password' => 'RahasiaKu123',
            'password_confirmation' => 'RahasiaKu123',
        ])->assertStatus(422);

        $this->assertDatabaseMissing('users', ['username' => 'budi_santoso']);
    }

    public function test_step_three_token_is_single_use()
    {
        $token = $this->tokenFor($this->existingMember());

        $body = [
            'token' => $token,
            'username' => 'budi_santoso',
            'password' => 'RahasiaKu123',
            'password_confirmation' => 'RahasiaKu123',
        ];

        $this->postJson('/api/koperasi/buat-akun', $body)->assertStatus(201);

        // Replaying the same token must not create a second account.
        $this->postJson('/api/koperasi/buat-akun', array_merge($body, ['username' => 'budi_dua']))
            ->assertStatus(422);

        $this->assertDatabaseMissing('users', ['username' => 'budi_dua']);
    }

    public function test_step_three_requires_matching_password_confirmation()
    {
        $token = $this->tokenFor($this->existingMember());

        $this->postJson('/api/koperasi/buat-akun', [
            'token' => $token,
            'username' => 'budi_santoso',
            'password' => 'RahasiaKu123',
            'password_confirmation' => 'BedaSekali123',
        ])->assertStatus(422)->assertJsonValidationErrors('password');
    }

    // ----------------------------------------------------------------- login

    public function test_koperasi_member_can_login_with_username_and_with_email()
    {
        $token = $this->tokenFor($this->existingMember());
        $this->postJson('/api/koperasi/buat-akun', [
            'token' => $token,
            'username' => 'budi_santoso',
            'password' => 'RahasiaKu123',
            'password_confirmation' => 'RahasiaKu123',
        ])->assertStatus(201);

        $this->postJson('/api/login', [
            'login' => 'budi_santoso',
            'password' => 'RahasiaKu123',
        ])->assertStatus(200)->assertJsonStructure(['access_token', 'user', 'permissions']);

        $this->postJson('/api/login', [
            'login' => 'budi@example.com',
            'password' => 'RahasiaKu123',
        ])->assertStatus(200)->assertJsonStructure(['access_token']);
    }

    public function test_existing_user_without_username_can_still_login_with_email()
    {
        $user = User::create([
            'name' => 'Alumni Lama',
            'email' => 'lama@example.com',
            'password' => Hash::make('password123'),
        ]);
        $user->assignRole('alumni_pembeli');

        $this->postJson('/api/login', [
            'email' => 'lama@example.com',
            'password' => 'password123',
        ])->assertStatus(200)->assertJsonStructure(['access_token']);
    }

    // ----------------------------------------------------------------- admin

    public function test_admin_approve_verifies_the_alumni_profile()
    {
        $token = $this->tokenFor($this->existingMember());
        $this->postJson('/api/koperasi/buat-akun', [
            'token' => $token,
            'username' => 'budi_santoso',
            'password' => 'RahasiaKu123',
            'password_confirmation' => 'RahasiaKu123',
        ])->assertStatus(201);

        $member = KoperasiMember::where('nim', '1801015001')->first();

        $admin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@perkasa.test',
            'password' => Hash::make('password123'),
        ]);
        $admin->assignRole('super_admin');
        $adminToken = $admin->createToken('test_token')->plainTextToken;

        $this->withHeaders(['Authorization' => "Bearer $adminToken"])
            ->postJson("/api/admin/koperasi/{$member->id}/verify", ['action' => 'approve'])
            ->assertStatus(200);

        $this->assertDatabaseHas('koperasi_members', [
            'id' => $member->id,
            'status' => 'approved',
        ]);
        $this->assertDatabaseHas('alumni_profiles', [
            'user_id' => $member->user_id,
            'status_verifikasi' => 'verified',
            'status_koperasi' => 'approved',
            'badge_verified' => true,
        ]);
    }

    public function test_admin_cannot_approve_before_activation()
    {
        $member = $this->existingMember(); // never activated: user_id is null

        $admin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@perkasa.test',
            'password' => Hash::make('password123'),
        ]);
        $admin->assignRole('super_admin');
        $adminToken = $admin->createToken('test_token')->plainTextToken;

        $this->withHeaders(['Authorization' => "Bearer $adminToken"])
            ->postJson("/api/admin/koperasi/{$member->id}/verify", ['action' => 'approve'])
            ->assertStatus(422);

        $this->assertDatabaseHas('koperasi_members', [
            'id' => $member->id,
            'status' => 'pending',
        ]);

        // Rejecting an un-activated registration is still allowed.
        $this->withHeaders(['Authorization' => "Bearer $adminToken"])
            ->postJson("/api/admin/koperasi/{$member->id}/verify", [
                'action' => 'reject',
                'reason' => 'Data tidak sesuai.',
            ])->assertStatus(200);

        $this->assertDatabaseHas('koperasi_members', [
            'id' => $member->id,
            'status' => 'rejected',
        ]);
    }

    public function test_non_admin_cannot_access_koperasi_admin_endpoints()
    {
        $user = User::create([
            'name' => 'Alumni Biasa',
            'email' => 'biasa@example.com',
            'password' => Hash::make('password123'),
        ]);
        $user->assignRole('alumni_pembeli');
        $token = $user->createToken('test_token')->plainTextToken;

        $this->withHeaders(['Authorization' => "Bearer $token"])
            ->getJson('/api/admin/koperasi')
            ->assertStatus(403);
    }
}
