<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;

class TestController extends Controller
{
    /**
     * Test endpoint untuk memastikan API + Response Helper bekerja
     */
    public function ping()
    {
        return ApiResponse::success([
            'message' => 'SPP Lite API is running',
            'version' => '1.0.0',
            'timestamp' => now()->toIso8601String(),
        ], 'API Health Check Successful');
    }
}
