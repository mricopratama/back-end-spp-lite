<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApiPingTest extends TestCase
{
    /**
     * Test that the API ping endpoint returns the correct response format.
     */
    public function test_ping_endpoint_returns_correct_response(): void
    {
        $response = $this->get('/api/ping');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'meta' => [
                    'code',
                    'status',
                    'message',
                ],
                'data' => [
                    'message',
                    'version',
                    'timestamp',
                ],
            ])
            ->assertJson([
                'meta' => [
                    'code' => 200,
                    'status' => 'success',
                    'message' => 'API Health Check Successful',
                ],
                'data' => [
                    'message' => 'SPP Lite API is running',
                    'version' => '1.0.0',
                ],
            ]);
    }
}
