<?php

use Illuminate\Support\Facades\Route;

// Test endpoint
Route::get('/ping', [App\Http\Controllers\Api\TestController::class, 'ping']);

// Public routes (no auth)
Route::prefix('auth')->group(function () {
    // Login endpoint (akan dibuat di commit berikutnya)
    // Route::post('/login', [AuthController::class, 'login']);
});

// Protected routes (require auth:sanctum)
Route::middleware('auth:sanctum')->group(function () {
    
    // Auth routes
    Route::prefix('auth')->group(function () {
        // Route::get('/me', [AuthController::class, 'me']);
        // Route::put('/change-password', [AuthController::class, 'changePassword']);
    });
    
    // Master Data routes
    Route::prefix('academic-years')->group(function () {
        // Will be implemented in next commits
    });
    
    Route::prefix('classes')->group(function () {
        // Will be implemented in next commits
    });
    
    Route::prefix('fee-categories')->group(function () {
        // Will be implemented in next commits
    });
    
    // Student routes
    Route::prefix('students')->group(function () {
        // Will be implemented in next commits
    });
    
    // Invoice routes
    Route::prefix('invoices')->group(function () {
        // Will be implemented in next commits
    });
    
    // Dashboard routes
    Route::prefix('dashboard')->group(function () {
        // Will be implemented in next commits
    });
    
    // Report routes
    Route::prefix('reports')->group(function () {
        // Will be implemented in next commits
    });
});
