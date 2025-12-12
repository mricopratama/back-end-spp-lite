<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\Student;
use App\Models\AcademicYear;
use App\Models\Classes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles
        Role::create(['id' => 1, 'name' => 'admin', 'description' => 'Administrator Sekolah']);
        Role::create(['id' => 2, 'name' => 'student', 'description' => 'Siswa/Wali Murid']);
    }

    public function test_login_with_valid_credentials_returns_token()
    {
        // Create admin user
        User::create([
            'username' => 'admin',
            'password_hash' => password_hash('password', PASSWORD_DEFAULT),
            'full_name' => 'Administrator Sekolah',
            'role_id' => 1,
            'student_id' => null,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'username' => 'admin',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'meta' => ['code', 'status', 'message'],
                'data' => [
                    'token',
                    'user' => ['id', 'username', 'full_name', 'role', 'student_id'],
                ],
            ])
            ->assertJson([
                'meta' => [
                    'code' => 200,
                    'status' => 'success',
                    'message' => 'Login successful',
                ],
                'data' => [
                    'user' => [
                        'username' => 'admin',
                        'full_name' => 'Administrator Sekolah',
                        'role' => 'admin',
                        'student_id' => null,
                    ],
                ],
            ]);

        $this->assertNotNull($response->json('data.token'));
    }

    public function test_login_with_invalid_credentials_returns_error()
    {
        User::create([
            'username' => 'admin',
            'password_hash' => password_hash('password', PASSWORD_DEFAULT),
            'full_name' => 'Administrator Sekolah',
            'role_id' => 1,
            'student_id' => null,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'username' => 'admin',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'meta' => [
                    'code' => 401,
                    'status' => 'error',
                    'message' => 'Invalid credentials',
                ],
            ]);
    }

    public function test_login_with_missing_fields_returns_validation_error()
    {
        $response = $this->postJson('/api/auth/login', [
            'username' => 'admin',
        ]);

        $response->assertStatus(422);
    }

    public function test_get_me_returns_authenticated_user_profile()
    {
        $user = User::create([
            'username' => 'admin',
            'password_hash' => password_hash('password', PASSWORD_DEFAULT),
            'full_name' => 'Administrator Sekolah',
            'role_id' => 1,
            'student_id' => null,
        ]);

        $token = $user->createToken('test_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/auth/me');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'meta' => ['code', 'status', 'message'],
                'data' => ['id', 'username', 'full_name', 'role'],
            ])
            ->assertJson([
                'data' => [
                    'username' => 'admin',
                    'full_name' => 'Administrator Sekolah',
                    'role' => 'admin',
                ],
            ]);
    }

    public function test_get_me_returns_student_details_for_student_user()
    {
        // Create a student
        $student = Student::create([
            'nis' => '1001',
            'full_name' => 'Budi Santoso',
            'address' => 'Jl. Example No. 1',
            'status' => 'active',
            'spp_base_fee' => 500000,
        ]);

        // Create academic year
        $academicYear = AcademicYear::create([
            'name' => '2024/2025',
            'is_active' => true,
        ]);

        // Create class
        $class = Classes::create([
            'name' => 'Kelas 1',
            'level' => '1',
        ]);

        // Add student to class
        \DB::table('student_class_history')->insert([
            'student_id' => $student->id,
            'class_id' => $class->id,
            'academic_year_id' => $academicYear->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create user for student
        $user = User::create([
            'username' => '1001',
            'password_hash' => password_hash('password', PASSWORD_DEFAULT),
            'full_name' => 'Budi Santoso',
            'role_id' => 2,
            'student_id' => $student->id,
        ]);

        $token = $user->createToken('test_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/auth/me');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'meta' => ['code', 'status', 'message'],
                'data' => [
                    'id',
                    'username',
                    'full_name',
                    'role',
                    'student_detail' => ['nis', 'class_name', 'status'],
                ],
            ])
            ->assertJson([
                'data' => [
                    'username' => '1001',
                    'full_name' => 'Budi Santoso',
                    'role' => 'student',
                    'student_detail' => [
                        'nis' => '1001',
                        'class_name' => 'Kelas 1',
                        'status' => 'ACTIVE',
                    ],
                ],
            ]);
    }

    public function test_get_me_without_token_returns_error()
    {
        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(401);
    }

    public function test_logout_revokes_token()
    {
        $user = User::create([
            'username' => 'admin',
            'password_hash' => password_hash('password', PASSWORD_DEFAULT),
            'full_name' => 'Administrator Sekolah',
            'role_id' => 1,
            'student_id' => null,
        ]);

        $token = $user->createToken('test_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'message' => 'Logged out successfully',
                ],
            ]);

        // Verify the token was actually deleted from the database
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
        ]);
    }

    public function test_change_password_with_valid_old_password()
    {
        $user = User::create([
            'username' => 'admin',
            'password_hash' => password_hash('password', PASSWORD_DEFAULT),
            'full_name' => 'Administrator Sekolah',
            'role_id' => 1,
            'student_id' => null,
        ]);

        $token = $user->createToken('test_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson('/api/auth/change-password', [
            'old_password' => 'password',
            'new_password' => 'newpassword123',
            'new_password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'message' => 'Password changed successfully',
                ],
            ]);

        // Verify new password works
        $user->refresh();
        $this->assertTrue(password_verify('newpassword123', $user->password_hash));
    }

    public function test_change_password_with_invalid_old_password()
    {
        $user = User::create([
            'username' => 'admin',
            'password_hash' => password_hash('password', PASSWORD_DEFAULT),
            'full_name' => 'Administrator Sekolah',
            'role_id' => 1,
            'student_id' => null,
        ]);

        $token = $user->createToken('test_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson('/api/auth/change-password', [
            'old_password' => 'wrongpassword',
            'new_password' => 'newpassword123',
            'new_password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'meta' => [
                    'message' => 'Old password is incorrect',
                ],
            ]);
    }

    public function test_change_password_with_mismatched_confirmation()
    {
        $user = User::create([
            'username' => 'admin',
            'password_hash' => password_hash('password', PASSWORD_DEFAULT),
            'full_name' => 'Administrator Sekolah',
            'role_id' => 1,
            'student_id' => null,
        ]);

        $token = $user->createToken('test_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson('/api/auth/change-password', [
            'old_password' => 'password',
            'new_password' => 'newpassword123',
            'new_password_confirmation' => 'differentpassword',
        ]);

        $response->assertStatus(422);
    }

    public function test_change_password_with_short_password()
    {
        $user = User::create([
            'username' => 'admin',
            'password_hash' => password_hash('password', PASSWORD_DEFAULT),
            'full_name' => 'Administrator Sekolah',
            'role_id' => 1,
            'student_id' => null,
        ]);

        $token = $user->createToken('test_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson('/api/auth/change-password', [
            'old_password' => 'password',
            'new_password' => '12345',
            'new_password_confirmation' => '12345',
        ]);

        $response->assertStatus(422);
    }
}
