<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Models\AcademicYear;
use App\Models\Classes;

class StudentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get active academic year
        $academicYear = AcademicYear::where('is_active', true)->first();

        if (!$academicYear) {
            $academicYear = AcademicYear::first();
        }

        // Get all classes
        $classes = Classes::all();

        // Create 50 sample students
        $students = [
            ['nis' => '2024001', 'full_name' => 'Ahmad Rizki Pratama', 'address' => 'Jl. Merdeka No. 10, Jakarta', 'phone_number' => '081234567890', 'status' => 'active'],
            ['nis' => '2024002', 'full_name' => 'Siti Nurhaliza', 'address' => 'Jl. Sudirman No. 25, Bandung', 'phone_number' => '081234567891', 'status' => 'active'],
            ['nis' => '2024003', 'full_name' => 'Budi Santoso', 'address' => 'Jl. Gatot Subroto No. 15, Surabaya', 'phone_number' => '081234567892', 'status' => 'active'],
            ['nis' => '2024004', 'full_name' => 'Dewi Lestari', 'address' => 'Jl. Ahmad Yani No. 8, Yogyakarta', 'phone_number' => '081234567893', 'status' => 'active'],
            ['nis' => '2024005', 'full_name' => 'Eko Prasetyo', 'address' => 'Jl. Diponegoro No. 12, Semarang', 'phone_number' => '081234567894', 'status' => 'active'],
            ['nis' => '2024006', 'full_name' => 'Farah Diba', 'address' => 'Jl. Veteran No. 5, Malang', 'phone_number' => '081234567895', 'status' => 'active'],
            ['nis' => '2024007', 'full_name' => 'Gilang Ramadhan', 'address' => 'Jl. Pahlawan No. 20, Solo', 'phone_number' => '081234567896', 'status' => 'active'],
            ['nis' => '2024008', 'full_name' => 'Hana Putri', 'address' => 'Jl. Pemuda No. 18, Medan', 'phone_number' => '081234567897', 'status' => 'active'],
            ['nis' => '2024009', 'full_name' => 'Irfan Hakim', 'address' => 'Jl. Kemerdekaan No. 7, Palembang', 'phone_number' => '081234567898', 'status' => 'active'],
            ['nis' => '2024010', 'full_name' => 'Julia Kartini', 'address' => 'Jl. Proklamasi No. 9, Makassar', 'phone_number' => '081234567899', 'status' => 'active'],
            ['nis' => '2024011', 'full_name' => 'Kurniawan Adi', 'address' => 'Jl. Majapahit No. 14, Denpasar', 'phone_number' => '081234567800', 'status' => 'active'],
            ['nis' => '2024012', 'full_name' => 'Lisa Anggraini', 'address' => 'Jl. Hayam Wuruk No. 11, Pontianak', 'phone_number' => '081234567801', 'status' => 'active'],
            ['nis' => '2024013', 'full_name' => 'Muhammad Fadli', 'address' => 'Jl. Sisingamangaraja No. 6, Padang', 'phone_number' => '081234567802', 'status' => 'active'],
            ['nis' => '2024014', 'full_name' => 'Nur Aini', 'address' => 'Jl. Cendrawasih No. 22, Manado', 'phone_number' => '081234567803', 'status' => 'active'],
            ['nis' => '2024015', 'full_name' => 'Omar Bakri', 'address' => 'Jl. Garuda No. 13, Balikpapan', 'phone_number' => '081234567804', 'status' => 'active'],
            ['nis' => '2024016', 'full_name' => 'Putri Ayu', 'address' => 'Jl. Pattimura No. 17, Samarinda', 'phone_number' => '081234567805', 'status' => 'active'],
            ['nis' => '2024017', 'full_name' => 'Qori Rahman', 'address' => 'Jl. Kartini No. 19, Jambi', 'phone_number' => '081234567806', 'status' => 'active'],
            ['nis' => '2024018', 'full_name' => 'Rina Safitri', 'address' => 'Jl. Teuku Umar No. 16, Pekanbaru', 'phone_number' => '081234567807', 'status' => 'active'],
            ['nis' => '2024019', 'full_name' => 'Sandi Wijaya', 'address' => 'Jl. Imam Bonjol No. 21, Banjarmasin', 'phone_number' => '081234567808', 'status' => 'active'],
            ['nis' => '2024020', 'full_name' => 'Tina Marlina', 'address' => 'Jl. Gajah Mada No. 23, Tasikmalaya', 'phone_number' => '081234567809', 'status' => 'active'],
            ['nis' => '2024021', 'full_name' => 'Udin Setiawan', 'address' => 'Jl. Jenderal Sudirman No. 24, Cirebon', 'phone_number' => '081234567810', 'status' => 'active'],
            ['nis' => '2024022', 'full_name' => 'Vina Marliana', 'address' => 'Jl. Supratman No. 26, Bogor', 'phone_number' => '081234567811', 'status' => 'active'],
            ['nis' => '2024023', 'full_name' => 'Wawan Kurniawan', 'address' => 'Jl. Asia Afrika No. 27, Depok', 'phone_number' => '081234567812', 'status' => 'active'],
            ['nis' => '2024024', 'full_name' => 'Yanti Suryani', 'address' => 'Jl. Mangga Dua No. 28, Bekasi', 'phone_number' => '081234567813', 'status' => 'active'],
            ['nis' => '2024025', 'full_name' => 'Zaki Abdullah', 'address' => 'Jl. Kebon Jeruk No. 29, Tangerang', 'phone_number' => '081234567814', 'status' => 'active'],
            ['nis' => '2023001', 'full_name' => 'Alif Firmansyah', 'address' => 'Jl. Kramat Raya No. 30, Jakarta', 'phone_number' => '081234567815', 'status' => 'active'],
            ['nis' => '2023002', 'full_name' => 'Bella Safira', 'address' => 'Jl. Cikini No. 31, Bandung', 'phone_number' => '081234567816', 'status' => 'active'],
            ['nis' => '2023003', 'full_name' => 'Candra Wijaya', 'address' => 'Jl. Menteng No. 32, Surabaya', 'phone_number' => '081234567817', 'status' => 'active'],
            ['nis' => '2023004', 'full_name' => 'Dina Mariana', 'address' => 'Jl. Thamrin No. 33, Yogyakarta', 'phone_number' => '081234567818', 'status' => 'active'],
            ['nis' => '2023005', 'full_name' => 'Edi Suryanto', 'address' => 'Jl. Kuningan No. 34, Semarang', 'phone_number' => '081234567819', 'status' => 'active'],
            ['nis' => '2022001', 'full_name' => 'Fitri Handayani', 'address' => 'Jl. Senopati No. 35, Malang', 'phone_number' => '081234567820', 'status' => 'graduated'],
            ['nis' => '2022002', 'full_name' => 'Galih Perdana', 'address' => 'Jl. Blora No. 36, Solo', 'phone_number' => '081234567821', 'status' => 'graduated'],
            ['nis' => '2022003', 'full_name' => 'Hesti Wulandari', 'address' => 'Jl. Cempaka No. 37, Medan', 'phone_number' => '081234567822', 'status' => 'graduated'],
            ['nis' => '2021001', 'full_name' => 'Ivan Gunawan', 'address' => 'Jl. Melati No. 38, Palembang', 'phone_number' => '081234567823', 'status' => 'inactive'],
            ['nis' => '2021002', 'full_name' => 'Jessica Mila', 'address' => 'Jl. Anggrek No. 39, Makassar', 'phone_number' => '081234567824', 'status' => 'inactive'],
        ];

        foreach ($students as $index => $studentData) {
            $student = Student::create($studentData);

            // Assign to a random class for the active academic year
            if ($classes->count() > 0 && $academicYear) {
                // Distribute students evenly across classes
                $classIndex = $index % $classes->count();
                $selectedClass = $classes[$classIndex];

                StudentClassHistory::create([
                    'student_id' => $student->id,
                    'class_id' => $selectedClass->id,
                    'academic_year_id' => $academicYear->id,
                ]);
            }
        }

        $this->command->info('Created ' . count($students) . ' sample students with class assignments');
    }
}
