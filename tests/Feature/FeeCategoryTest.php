<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\FeeCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeeCategoryTest extends TestCase
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

    public function test_can_list_fee_categories()
    {
        FeeCategory::create([
            'name' => 'SPP',
            'default_amount' => 500000,
            'description' => 'Sumbangan Pembinaan Pendidikan',
        ]);
        FeeCategory::create([
            'name' => 'Buku',
            'default_amount' => 300000,
            'description' => 'Uang Buku Pelajaran',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->getJson('/api/fee-categories');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'meta' => ['code', 'status', 'message'],
                'data' => [
                    '*' => ['id', 'name', 'default_amount', 'description'],
                ],
            ]);
    }

    public function test_can_create_fee_category()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->postJson('/api/fee-categories', [
            'name' => 'SPP',
            'default_amount' => 500000,
            'description' => 'Sumbangan Pembinaan Pendidikan',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'meta' => [
                    'code' => 201,
                    'status' => 'success',
                ],
                'data' => [
                    'name' => 'SPP',
                    'default_amount' => '500000.00',
                    'description' => 'Sumbangan Pembinaan Pendidikan',
                ],
            ]);

        $this->assertDatabaseHas('fee_categories', [
            'name' => 'SPP',
            'default_amount' => 500000,
        ]);
    }

    public function test_can_create_fee_category_without_description()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->postJson('/api/fee-categories', [
            'name' => 'SPP',
            'default_amount' => 500000,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('fee_categories', [
            'name' => 'SPP',
            'default_amount' => 500000,
        ]);
    }

    public function test_can_update_fee_category()
    {
        $feeCategory = FeeCategory::create([
            'name' => 'SPP',
            'default_amount' => 500000,
            'description' => 'Old description',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->putJson('/api/fee-categories/' . $feeCategory->id, [
            'name' => 'SPP Updated',
            'default_amount' => 600000,
            'description' => 'New description',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'name' => 'SPP Updated',
                    'default_amount' => '600000.00',
                    'description' => 'New description',
                ],
            ]);
    }

    public function test_can_delete_fee_category()
    {
        $feeCategory = FeeCategory::create([
            'name' => 'SPP',
            'default_amount' => 500000,
            'description' => 'Test',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->deleteJson('/api/fee-categories/' . $feeCategory->id);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('fee_categories', ['id' => $feeCategory->id]);
    }

    public function test_validation_requires_positive_amount()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->postJson('/api/fee-categories', [
            'name' => 'SPP',
            'default_amount' => -100,
        ]);

        $response->assertStatus(422);
    }

    public function test_validation_fails_with_missing_fields()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->postJson('/api/fee-categories', []);

        $response->assertStatus(422);
    }
}
