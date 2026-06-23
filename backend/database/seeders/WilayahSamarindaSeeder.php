<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WilayahSamarindaSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            'Palaran' => [
                'Rawa Makmur', 'Handil Bakti', 'Bukuan', 'Simpang Pasir', 'Bantuas',
            ],
            'Samarinda Seberang' => [
                'Sungai Keledang', 'Baqa', 'Mesjid', 'Mangkupalas', 'Tenun', 'Gunung Panjang',
            ],
            'Samarinda Ulu' => [
                'Teluk Lerong Ilir', 'Jawa', 'Air Putih', 'Sidodadi', 'Air Hitam', 'Dadi Mulya', 'Gunung Kelua', 'Bukit Pinang',
            ],
            'Samarinda Ilir' => [
                'Selili', 'Sungai Dama', 'Sidomulyo', 'Sidodamai', 'Pelita',
            ],
            'Samarinda Utara' => [
                'Sempaja Selatan', 'Lempake', 'Sungai Siring', 'Sempaja Utara', 'Tanah Merah', 'Sempaja Barat', 'Sempaja Timur', 'Budaya Pampang',
            ],
            'Sungai Kunjang' => [
                'Loa Bakung', 'Loa Buah', 'Karang Asam Ulu', 'Lok Bahu', 'Teluk Lerong Ulu', 'Karang Asam Ilir', 'Karang Anyar',
            ],
            'Sambutan' => [
                'Sungai Kapih', 'Sambutan', 'Makroman', 'Sindang Sari', 'Pulau Atas',
            ],
            'Sungai Pinang' => [
                'Temindung Permai', 'Sungai Pinang Dalam', 'Gunung Lingai', 'Mugirejo', 'Bandara',
            ],
            'Samarinda Kota' => [
                'Karang Mumus', 'Pelabuhan', 'Pasar Pagi', 'Bugis', 'Sungai Pinang Luar',
            ],
            'Loa Janan Ilir' => [
                'Simpang Tiga', 'Tani Aman', 'Sengkotek', 'Harapan Baru', 'Rapak Dalam',
            ],
        ];

        $rows = [];
        foreach ($data as $kecamatan => $kelurahanList) {
            foreach ($kelurahanList as $kelurahan) {
                $rows[] = [
                    'kecamatan' => $kecamatan,
                    'kelurahan' => $kelurahan,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        DB::table('wilayah')->insert($rows);
    }
}
