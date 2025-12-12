<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\AcademicYear;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademicYearTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $adminToken;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles
        Role::create(['id' => 1, 'name' => 'admin', 'description' => 'Administrator Sekolah']);
        Role::create(['id' => 2, 'name' => 'student', 'description' => 'Siswa/Wali Murid']);

        // Create admin user
        $this->adminUser = User::create([
            'username' => 'admin',
            'password_hash' => password_hash('password', PASSWORD_DEFAULT),
            'full_name' => 'Administrator',
            'role_id' => 1,
            'student_id' => null,
        ]);

        // Get admin token
        $response = $this->postJson('/api/auth/login', [
            'username' => 'admin',
            'password' => 'password',
        ]);
        $this->adminToken = $response->json('data.token');
    }

    public function test_can_list_academic_years()
    {
        AcademicYear::create(['name' => '2023/2024', 'is_active' => false]);
        AcademicYear::create(['name' => '2024/2025', 'is_active' => true]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->getJson('/api/academic-years');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'meta' => ['code', 'status', 'message'],
                'data' => [
                    '*' => ['id', 'name', 'is_active'],
                ],
            ]);
    }

    public function test_can_create_academic_year()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->postJson('/api/academic-years', [
            'name' => '2024/2025',
            'is_active' => true,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'meta' => [
                    'code' => 201,
                    'status' => 'success',
                ],
                'data' => [
                    'name' => '2024/2025',
                    'is_active' => true,
                ],
            ]);

        $this->assertDatabaseHas('academic_years', [
            'name' => '2024/2025',
            'is_active' => true,
        ]);
    }

    public function test_creating_active_academic_year_deactivates_others()
    {
        AcademicYear::create(['name' => '2023/2024', 'is_active' => true]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->postJson('/api/academic-years', [
            'name' => '2024/2025',
            'is_active' => true,
        ]);

        $response->assertStatus(201);

        // Check that old active year is now inactive
        $this->assertDatabaseHas('academic_years', [
            'name' => '2023/2024',
            'is_active' => false,
        ]);

        // Check that new year is active
        $this->assertDatabaseHas('academic_years', [
            'name' => '2024/2025',
            'is_active' => true,
        ]);
    }

    public function test_can_update_academic_year()
    {
        $academicYear = AcademicYear::create(['name' => '2023/2024', 'is_active' => false]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->putJson('/api/academic-years/' . $academicYear->id, [
            'name' => '2023/2024 Updated',
            'is_active' => true,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'name' => '2023/2024 Updated',
                    'is_active' => true,
                ],
            ]);
    }

    public function test_cannot_delete_active_academic_year()
    {
        $academicYear = AcademicYear::create(['name' => '2024/2025', 'is_active' => true]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->deleteJson('/api/academic-years/' . $academicYear->id);

        $response->assertStatus(400)
            ->assertJson([
                'meta' => [
                    'message' => 'Cannot delete active academic year or data is in use.',
                ],
            ]);
    }

    public function test_can_delete_inactive_academic_year()
    {
        $academicYear = AcademicYear::create(['name' => '2023/2024', 'is_active' => false]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->deleteJson('/api/academic-years/' . $academicYear->id);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('academic_years', ['id' => $academicYear->id]);
    }

    public function test_validation_fails_with_missing_fields()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->postJson('/api/academic-years', []);

        $response->assertStatus(422);
    }

    public function test_requires_admin_role()
    {
        // Create student user
        $studentUser = User::create([
            'username' => 'student',
            'password_hash' => password_hash('password', PASSWORD_DEFAULT),
            'full_name' => 'Student User',
            'role_id' => 2,
            'student_id' => null,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'username' => 'student',
            'password' => 'password',
        ]);
        $studentToken = $response->json('data.token');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $studentToken,
        ])->getJson('/api/academic-years');

        $response->assertStatus(403);
    }
}
