<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\AcademicYearController;
use App\Http\Controllers\API\ClassController;
use App\Http\Controllers\API\FeeCategoryController;

// Test endpoint
Route::get('/ping', [App\Http\Controllers\Api\TestController::class, 'ping']);

// Public routes (no auth)
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
});

// Protected routes (require auth:sanctum)
Route::middleware('auth:sanctum')->group(function () {

    // Auth routes
    Route::prefix('auth')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::put('/change-password', [AuthController::class, 'changePassword']);
    });

    // Master Data routes (Admin only)
    Route::middleware('role:admin')->group(function () {
        Route::apiResource('academic-years', AcademicYearController::class);
        Route::apiResource('classes', ClassController::class);
        Route::apiResource('fee-categories', FeeCategoryController::class);
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
