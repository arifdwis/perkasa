<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);

        // Seed Super Admin
        $admin = User::create([
            'name' => 'Super Admin FEB',
            'email' => 'admin@perkasa.test',
            'password' => bcrypt('password123'),
        ]);
        $admin->assignRole('super_admin');

        // Seed verified Alumni Buyer
        $buyer = User::create([
            'name' => 'Alumni Pembeli',
            'email' => 'buyer@perkasa.test',
            'password' => bcrypt('password123'),
        ]);
        $buyer->assignRole('alumni_pembeli');
        $buyer->profile()->create([
            'nim' => '1801015001',
            'program_studi' => 'S1 Manajemen',
            'tahun_masuk' => 2018,
            'tahun_lulus' => 2022,
            'whatsapp' => '081234567890',
            'status_verifikasi' => 'verified',
            'badge_verified' => true,
        ]);

        // Seed verified Alumni Seller
        $seller = User::create([
            'name' => 'Alumni Penjual',
            'email' => 'seller@perkasa.test',
            'password' => bcrypt('password123'),
        ]);
        $seller->assignRole('alumni_penjual');
        $seller->profile()->create([
            'nim' => '1801015002',
            'program_studi' => 'S1 Akuntansi',
            'tahun_masuk' => 2018,
            'tahun_lulus' => 2022,
            'whatsapp' => '081234567891',
            'status_verifikasi' => 'verified',
            'badge_verified' => true,
        ]);
    }
}
