<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\AcademicYearController;
use App\Http\Controllers\API\ClassController;
use App\Http\Controllers\API\FeeCategoryController;
use App\Http\Controllers\API\StudentController;
use App\Http\Controllers\API\InvoiceController;
use App\Http\Controllers\API\DashboardController;
use App\Http\Controllers\API\ReportController;

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
        Route::get('/', [StudentController::class, 'index']); // List students
        Route::post('/', [StudentController::class, 'store'])->middleware('role:admin'); // Create student
        Route::get('/{student}', [StudentController::class, 'show']); // Show student detail
        Route::put('/{student}', [StudentController::class, 'update'])->middleware('role:admin'); // Update student
        Route::delete('/{student}', [StudentController::class, 'destroy'])->middleware('role:admin'); // Delete student

        // Student specific actions (Admin only)
        Route::post('/set-class', [StudentController::class, 'setClass'])->middleware('role:admin');
        Route::post('/bulk-promote', [StudentController::class, 'bulkPromote'])->middleware('role:admin');
        Route::post('/{student}/create-user', [StudentController::class, 'createUserAccount'])->middleware('role:admin');
        Route::post('/import', [StudentController::class, 'import'])->middleware('role:admin');
    });

    // Invoice routes
    Route::prefix('invoices')->group(function () {
        Route::get('/', [InvoiceController::class, 'index']); // List invoices
        Route::post('/', [InvoiceController::class, 'store'])->middleware('role:admin'); // Create single invoice
        Route::post('/bulk', [InvoiceController::class, 'bulkStore'])->middleware('role:admin'); // Create bulk invoices
        Route::get('/my', [InvoiceController::class, 'myInvoices']); // Student: my invoices
        Route::get('/{invoice}', [InvoiceController::class, 'show']); // Show invoice detail
        Route::delete('/{invoice}', [InvoiceController::class, 'destroy'])->middleware('role:admin'); // Delete invoice
        Route::post('/import', [InvoiceController::class, 'import'])->middleware('role:admin'); // Import from Excel
    });

    // Dashboard routes
    Route::prefix('dashboard')->group(function () {
        Route::get('/admin', [DashboardController::class, 'adminStats'])->middleware('role:admin');
        Route::get('/student', [DashboardController::class, 'studentStats']);
        Route::get('/monthly-income', [DashboardController::class, 'monthlyIncome'])->middleware('role:admin');
        Route::get('/payment-breakdown', [DashboardController::class, 'paymentMethodBreakdown'])->middleware('role:admin');
    });

    // Report routes (Admin only)
    Route::prefix('reports')->middleware('role:admin')->group(function () {
        Route::get('/arrears', [ReportController::class, 'arrears']); // Laporan tunggakan
        Route::get('/income', [ReportController::class, 'income']); // Laporan pendapatan
        Route::get('/expected-income', [ReportController::class, 'expectedIncome']); // Laporan rencana pemasukan
        Route::get('/class', [ReportController::class, 'classReport']); // Laporan per kelas
        Route::get('/payment-history', [ReportController::class, 'paymentHistory']); // History pembayaran
    });
});
