<?php

namespace Tests\Feature;

use App\Models\ImportedAlumniRecord;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AlumniVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    /**
     * Test admin can import alumni via CSV.
     */
    public function test_admin_can_import_alumni_csv()
    {
        $admin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@perkasa.test',
            'password' => Hash::make('password123'),
        ]);
        $admin->assignRole('super_admin');
        $token = $admin->createToken('test_token')->plainTextToken;

        // Create a CSV mock with heading columns
        $csvContent = "nim,nama,program_studi,tahun_masuk,tahun_lulus,email,whatsapp\n".
                      "1801015099,Official Alumni,S1 Manajemen,2018,2022,official@example.com,081234567899\n";

        $file = UploadedFile::fake()->createWithContent('alumni.csv', $csvContent);

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->postJson('/api/admin/alumni/import', [
            'file' => $file,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('imported_alumni_records', [
            'nim' => '1801015099',
            'name' => 'Official Alumni',
            'email' => 'official@example.com',
        ]);
    }

    /**
     * Test automatic verification if NIM & Email matches imported record.
     */
    public function test_registration_auto_verifies_if_nim_matches()
    {
        // 1. Pre-seed imported records
        ImportedAlumniRecord::create([
            'nim' => '1801015099',
            'name' => 'Official Alumni',
            'program_studi' => 'S1 Manajemen',
            'tahun_masuk' => 2018,
            'tahun_lulus' => 2022,
            'email' => 'official@example.com',
            'whatsapp' => '081234567899',
        ]);

        // 2. Register user with matching NIM and Email
        $response = $this->postJson('/api/register', [
            'name' => 'Official Alumni User',
            'email' => 'official@example.com',
            'password' => 'password123',
            'nim' => '1801015099',
            'program_studi' => 'S1 Manajemen',
            'tahun_masuk' => 2018,
            'tahun_lulus' => 2022,
            'whatsapp' => '081234567899',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('user.profile.status_verifikasi', 'verified');
        $response->assertJsonPath('user.profile.badge_verified', true);
    }

    /**
     * Test registration gets pending status if NIM does not match.
     */
    public function test_registration_gets_pending_status_if_nim_does_not_match()
    {
        // Register user with non-matching NIM
        $response = $this->postJson('/api/register', [
            'name' => 'Unverified Alumni',
            'email' => 'unverified@example.com',
            'password' => 'password123',
            'nim' => '1801015999', // Unknown NIM
            'program_studi' => 'S1 Akuntansi',
            'tahun_masuk' => 2018,
            'tahun_lulus' => 2022,
            'whatsapp' => '081234567888',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('user.profile.status_verifikasi', 'pending');
        $response->assertJsonPath('user.profile.badge_verified', false);
    }

    /**
     * Test admin can view and manually verify alumni.
     */
    public function test_admin_can_manually_verify_alumni()
    {
        $admin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@perkasa.test',
            'password' => Hash::make('password123'),
        ]);
        $admin->assignRole('super_admin');
        $token = $admin->createToken('test_token')->plainTextToken;

        // Register a pending user
        $user = User::create([
            'name' => 'Pending Alumni',
            'email' => 'pending@example.com',
            'password' => Hash::make('password123'),
        ]);
        $user->assignRole('alumni_pembeli');
        $profile = $user->profile()->create([
            'nim' => '1801015666',
            'program_studi' => 'S1 Akuntansi',
            'tahun_masuk' => 2018,
            'tahun_lulus' => 2022,
            'whatsapp' => '081234567666',
            'status_verifikasi' => 'pending',
        ]);

        // Verify profile
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->postJson("/api/admin/alumni/{$profile->id}/verify", [
            'action' => 'approve',
        ]);

        $response->assertStatus(200);
        $this->assertEquals('verified', $profile->fresh()->status_verifikasi);
        $this->assertTrue($profile->fresh()->badge_verified);

        // Assert verification history creation
        $this->assertDatabaseHas('alumni_verifications', [
            'alumni_profile_id' => $profile->id,
            'admin_user_id' => $admin->id,
            'action' => 'approve',
        ]);
    }
}
