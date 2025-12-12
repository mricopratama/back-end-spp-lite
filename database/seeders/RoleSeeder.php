<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Seed 2 roles
        $roles = [
            [
                'id' => 1,
                'name' => 'admin',
                'description' => 'Administrator Sekolah',
            ],
            [
                'id' => 2,
                'name' => 'student',
                'description' => 'Siswa/Wali Murid',
            ],
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }

        // Create default admin user
        User::create([
            'username' => 'admin',
            'password_hash' => password_hash('password', PASSWORD_DEFAULT),
            'full_name' => 'Administrator Sekolah',
            'role_id' => 1,
            'student_id' => null,
        ]);
    }
}
