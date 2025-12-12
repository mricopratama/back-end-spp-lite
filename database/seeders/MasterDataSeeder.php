<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\AcademicYear;
use App\Models\Classes;
use App\Models\FeeCategory;

class MasterDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Seed Academic Year
        AcademicYear::create([
            'name' => '2024/2025',
            'is_active' => true,
        ]);

        // Seed Classes (SD Level 1-6)
        $classes = [
            ['name' => 'Kelas 1', 'level' => 1],
            ['name' => 'Kelas 2', 'level' => 2],
            ['name' => 'Kelas 3', 'level' => 3],
            ['name' => 'Kelas 4', 'level' => 4],
            ['name' => 'Kelas 5', 'level' => 5],
            ['name' => 'Kelas 6', 'level' => 6],
        ];

        foreach ($classes as $class) {
            Classes::create($class);
        }

        // Seed Fee Categories
        $feeCategories = [
            [
                'name' => 'SPP',
                'default_amount' => 500000,
                'description' => 'Sumbangan Pembinaan Pendidikan',
            ],
            [
                'name' => 'Buku',
                'default_amount' => 300000,
                'description' => 'Uang Buku Pelajaran',
            ],
            [
                'name' => 'Ekskul',
                'default_amount' => 200000,
                'description' => 'Kegiatan Ekstrakurikuler',
            ],
            [
                'name' => 'Daftar Ulang',
                'default_amount' => 1000000,
                'description' => 'Biaya Daftar Ulang Tahunan',
            ],
        ];

        foreach ($feeCategories as $category) {
            FeeCategory::create($category);
        }
    }
}

