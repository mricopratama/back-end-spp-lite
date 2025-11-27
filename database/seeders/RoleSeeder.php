<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'Admin',
                'description' => 'Administrator with full access',
            ],
            [
                'name' => 'Staff',
                'description' => 'Staff member with limited access',
            ],
            [
                'name' => 'Student',
                'description' => 'Student with view-only access',
            ],
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }
    }
}
