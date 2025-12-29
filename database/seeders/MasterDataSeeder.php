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
        // Seed Academic Year 2018/2019 s.d. 2025/2026
        $firstYear = 2018;
        $lastYear = 2025;
        for ($y = $firstYear; $y <= $lastYear; $y++) {
            AcademicYear::create([
                'name' => $y . '/' . ($y + 1),
                'is_active' => ($y == $lastYear),
            ]);
        }

        // Seed Classes (SD Level 1-6)
        $classes = [
            ['name' => 'Kelas 1.1', 'level' => 1],
            ['name' => 'Kelas 1.2', 'level' => 1],
            ['name' => 'Kelas 2.1', 'level' => 2],
            ['name' => 'Kelas 2.2', 'level' => 2],
            ['name' => 'Kelas 3.1', 'level' => 3],
            ['name' => 'Kelas 3.2', 'level' => 3],
            ['name' => 'Kelas 4.1', 'level' => 4],
            ['name' => 'Kelas 4.2', 'level' => 4],
            ['name' => 'Kelas 5.1', 'level' => 5],
            ['name' => 'Kelas 5.2', 'level' => 5],
            ['name' => 'Kelas 6.1', 'level' => 6],
            ['name' => 'Kelas 6.2', 'level' => 6],
        ];

        foreach ($classes as $class) {
            Classes::create($class);
        }
    }
}

