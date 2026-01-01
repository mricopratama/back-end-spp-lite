<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\AcademicYearController;
use App\Http\Controllers\API\ClassController;
use App\Http\Controllers\API\FeeCategoryController;
use App\Http\Controllers\API\StudentController;
use App\Http\Controllers\API\InvoiceController;
use App\Http\Controllers\API\PaymentController;
use App\Http\Controllers\API\NotificationController;
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
        Route::get('/paginate', [StudentController::class, 'paginate']); // Paginated students with filters
        Route::get('/search', [StudentController::class, 'search']); // Search students by name/nis & academic year
        Route::post('/', [StudentController::class, 'store'])->middleware('role:admin'); // Create student
        Route::get('/{student}', [StudentController::class, 'show']); // Show student detail
        Route::put('/{student}', [StudentController::class, 'update'])->middleware('role:admin'); // Update student
        Route::delete('/{student}', [StudentController::class, 'destroy'])->middleware('role:admin'); // Delete student

        // Student specific actions (Admin only)
        Route::post('/set-class', [StudentController::class, 'setClass'])->middleware('role:admin');
        Route::get('/bulk-promote/preview', [StudentController::class, 'bulkPromotePreview'])->middleware('role:admin');
        Route::post('/bulk-promote/auto', [StudentController::class, 'bulkPromoteAuto'])->middleware('role:admin');
        Route::post('/bulk-promote', [StudentController::class, 'bulkPromote'])->middleware('role:admin');
        Route::post('/{student}/create-user', [StudentController::class, 'createUserAccount'])->middleware('role:admin');
        Route::post('/import', [StudentController::class, 'import'])->middleware('role:admin');

        // SPP Card (Kartu SPP Digital)
        Route::get('/{student}/spp-card', [StudentController::class, 'sppCard']); // SPP card for specific student
        Route::get('/my/spp-card', [StudentController::class, 'mySppCard']); // My SPP card (Wali Murid)
    });

    // Invoice routes
    Route::prefix('invoices')->group(function () {
        Route::get('/', [InvoiceController::class, 'index']); // List invoices
        Route::post('/', [InvoiceController::class, 'store'])->middleware('role:admin'); // Create single invoice
        Route::post('/bulk', [InvoiceController::class, 'bulkStore'])->middleware('role:admin'); // Create bulk invoices

        // Monthly SPP Generation (NEW)
        Route::post('/generate-monthly-spp', [InvoiceController::class, 'generateMonthlySpp'])->middleware('role:admin');
        Route::post('/generate-missing-months', [InvoiceController::class, 'generateMissingMonths'])->middleware('role:admin');
        Route::get('/monthly-status/{studentId}', [InvoiceController::class, 'getMonthlyPaymentStatus']);

        Route::post('/import', [InvoiceController::class, 'import'])->middleware('role:admin'); // Import from Excel
        Route::get('/my', [InvoiceController::class, 'myInvoices']); // Student: my invoices (MUST be before /{invoice})
        Route::get('/{invoice}', [InvoiceController::class, 'show']); // Show invoice detail
        Route::delete('/{invoice}', [InvoiceController::class, 'destroy'])->middleware('role:admin'); // Delete invoice
    });

    // Payment routes
    Route::prefix('payments')->group(function () {
        Route::get('/', [PaymentController::class, 'index']); // List payments
        Route::post('/', [PaymentController::class, 'store'])->middleware('role:admin'); // Record payment (Admin only)
        Route::get('/history', [PaymentController::class, 'paymentHistory']); // Payment history with date range
        Route::get('/my', [PaymentController::class, 'myPayments']); // Student: my payment history
        Route::get('/student/{studentId}', [PaymentController::class, 'studentHistory']); // Payment history by student
        Route::get('/{payment}', [PaymentController::class, 'show']); // Show payment detail
        Route::get('/{payment}/print', [PaymentController::class, 'printReceipt']); // Print receipt
        Route::delete('/{payment}', [PaymentController::class, 'destroy'])->middleware('role:admin'); // Delete payment (Admin only)
    });

    // Notification routes
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']); // List notifications
        Route::get('/unread-count', [NotificationController::class, 'unreadCount']); // Get unread count
        Route::put('/{notification}/read', [NotificationController::class, 'markAsRead']); // Mark as read
        Route::put('/read-all', [NotificationController::class, 'markAllAsRead']); // Mark all as read
        Route::delete('/{notification}', [NotificationController::class, 'destroy']); // Delete notification
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
