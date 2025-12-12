<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\Classes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassTest extends TestCase
{
    use RefreshDatabase;

    protected $adminToken;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles
        Role::create(['id' => 1, 'name' => 'admin', 'description' => 'Administrator Sekolah']);

        // Create admin user
        User::create([
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

    public function test_can_list_classes()
    {
        Classes::create(['name' => 'Kelas 1', 'level' => 1]);
        Classes::create(['name' => 'Kelas 2', 'level' => 2]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->getJson('/api/classes');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'meta' => ['code', 'status', 'message'],
                'data' => [
                    '*' => ['id', 'name', 'level'],
                ],
            ]);
    }

    public function test_can_create_class()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->postJson('/api/classes', [
            'name' => 'Kelas 1',
            'level' => 1,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'meta' => [
                    'code' => 201,
                    'status' => 'success',
                ],
                'data' => [
                    'name' => 'Kelas 1',
                    'level' => 1,
                ],
            ]);

        $this->assertDatabaseHas('classes', [
            'name' => 'Kelas 1',
            'level' => 1,
        ]);
    }

    public function test_can_update_class()
    {
        $class = Classes::create(['name' => 'Kelas 1', 'level' => 1]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->putJson('/api/classes/' . $class->id, [
            'name' => 'Kelas 1 Updated',
            'level' => 1,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'name' => 'Kelas 1 Updated',
                    'level' => 1,
                ],
            ]);
    }

    public function test_can_delete_class()
    {
        $class = Classes::create(['name' => 'Kelas 1', 'level' => 1]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->deleteJson('/api/classes/' . $class->id);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('classes', ['id' => $class->id]);
    }

    public function test_validation_requires_level_between_1_and_6()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->postJson('/api/classes', [
            'name' => 'Kelas 7',
            'level' => 7,
        ]);

        $response->assertStatus(422);
    }

    public function test_validation_fails_with_missing_fields()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->postJson('/api/classes', []);

        $response->assertStatus(422);
    }
}
