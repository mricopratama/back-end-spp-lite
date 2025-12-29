<?php

namespace Database\Seeders;

use App\Models\FeeCategory;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class FeeCategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        // Seed Fee Categories
        $feeCategories = [
            [
                'name' => 'SPP 1 200.000',
                'default_amount' => 200000,
                'description' => 'SPP Bulanan',
            ],
            [
                'name' => 'SPP 2 190.000',
                'default_amount' => 190000,
                'description' => 'SPP Bulanan',
            ],
            [
                'name' => 'SPP 3 185.000',
                'default_amount' => 185000,
                'description' => 'SPP Bulanan',
            ],
            [
                'name' => 'SPP 4 180.000',
                'default_amount' => 180000,
                'description' => 'SPP Bulanan',
            ],
            [
                'name' => 'SPP 5 175.000',
                'default_amount' => 175000,
                'description' => 'SPP Bulanan',
            ],
            [
                'name' => 'SPP 6 170.000',
                'default_amount' => 170000,
                'description' => 'SPP Bulanan',
            ],
            [
                'name' => 'SPP 7 160.000',
                'default_amount' => 160000,
                'description' => 'SPP Bulanan',
            ],
            [
                'name' => 'SPP 8 150.000',
                'default_amount' => 150000,
                'description' => 'SPP Bulanan',
            ],
            [
                'name' => 'SPP 9 140.000',
                'default_amount' => 140000,
                'description' => 'SPP Bulanan',
            ],
            [
                'name' => 'SPP 10 135.000',
                'default_amount' => 135000,
                'description' => 'SPP Bulanan',
            ],
            [
                'name' => 'SPP 11 130.000',
                'default_amount' => 130000,
                'description' => 'SPP Bulanan',
            ],
            [
                'name' => 'SPP 12 126.000',
                'default_amount' => 126000,
                'description' => 'SPP Bulanan',
            ],
            [
                'name' => 'SPP 13 125.000',
                'default_amount' => 125000,
                'description' => 'SPP Bulanan',
            ],
            [
                'name' => 'SPP 14 120.000',
                'default_amount' => 120000,
                'description' => 'SPP Bulanan',
            ],
            [
                'name' => 'SPP 15 115.000',
                'default_amount' => 115000,
                'description' => 'SPP Bulanan',
            ],
            [
                'name' => 'SPP 16 112.000',
                'default_amount' => 112000,
                'description' => 'SPP Bulanan',
            ],
            [
                'name' => 'SPP 17 110.000',
                'default_amount' => 110000,
                'description' => 'SPP Bulanan',
            ],
            [
                'name' => 'SPP 18 100.000',
                'default_amount' => 100000,
                'description' => 'SPP Bulanan',
            ],
            [
                'name' => 'SPP 19 90.000',
                'default_amount' => 90000,
                'description' => 'SPP Bulanan',
            ],
            [
                'name' => 'SPP 20 85.000',
                'default_amount' => 85000,
                'description' => 'SPP Bulanan',
            ],
            [
                'name' => 'SPP 21 80.000',
                'default_amount' => 80000,
                'description' => 'SPP Bulanan',
            ],
            [
                'name' => 'SPP 22 75.000',
                'default_amount' => 75000,
                'description' => 'SPP Bulanan',
            ],
            [
                'name' => 'SPP 23 50.000',
                'default_amount' => 50000,
                'description' => 'SPP Bulanan',
            ],
            [
                'name' => 'SPP 24 25.000',
                'default_amount' => 25000,
                'description' => 'SPP Bulanan',
            ],
            [
                'name' => 'Buku Kelas 1 Tahun 25/26',
                'default_amount' => 650000,
                'description' => 'Uang Buku Pelajaran',
            ],
            [
                'name' => 'Buku Kelas 2 Tahun 25/26',
                'default_amount' => 650000,
                'description' => 'Uang Buku Pelajaran',
            ],
            [
                'name' => 'Buku Kelas 3 Tahun 25/26',
                'default_amount' => 850000,
                'description' => 'Uang Buku Pelajaran',
            ],
            [
                'name' => 'Buku Kelas 4 Tahun 25/26',
                'default_amount' => 990000,
                'description' => 'Uang Buku Pelajaran',
            ],
            [
                'name' => 'Buku Kelas 5 Tahun 25/26',
                'default_amount' => 975000,
                'description' => 'Uang Buku Pelajaran',
            ],
            [
                'name' => 'Buku Kelas 6 Tahun 25/26',
                'default_amount' => 885000,
                'description' => 'Uang Buku Pelajaran',
            ],
            // Ekstrakurikuler
            [
                'name' => 'ENGLISH CLUB',
                'default_amount' => 450000,
                'description' => 'Kegiatan Ekstrakurikuler English Club',
            ],
            [
                'name' => 'SENI LUKIS',
                'default_amount' => 400000,
                'description' => 'Kegiatan Ekstrakurikuler Seni Lukis',
            ],
            [
                'name' => 'BADMINTON',
                'default_amount' => 500000,
                'description' => 'Kegiatan Ekstrakurikuler Badminton',
            ],
            [
                'name' => 'RENANG',
                'default_amount' => 600000,
                'description' => 'Kegiatan Ekstrakurikuler Renang',
            ],
            [
                'name' => 'FUTSAL',
                'default_amount' => 600000,
                'description' => 'Kegiatan Ekstrakurikuler Futsal',
            ],
            [
                'name' => 'KOMPUTER (IT)',
                'default_amount' => 450000,
                'description' => 'Kegiatan Ekstrakurikuler Komputer (IT)',
            ],
            [
                'name' => 'QIROAH',
                'default_amount' => 400000,
                'description' => 'Kegiatan Ekstrakurikuler Qiroah',
            ],
            [
                'name' => 'TPA',
                'default_amount' => 400000,
                'description' => 'Kegiatan Ekstrakurikuler TPA',
            ],
            [
                'name' => 'SENI VOCAL',
                'default_amount' => 450000,
                'description' => 'Kegiatan Ekstrakurikuler Seni Vocal',
            ],
            [
                'name' => 'TARI',
                'default_amount' => 450000,
                'description' => 'Kegiatan Ekstrakurikuler Tari',
            ],
            [
                'name' => 'Daftar Ulang',
                'default_amount' => 745000,
                'description' => 'Biaya Daftar Ulang',
            ],
        ];

        foreach ($feeCategories as $category) {
            FeeCategory::create($category);
        }
    }
}
