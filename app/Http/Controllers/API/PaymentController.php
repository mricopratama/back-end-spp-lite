<?php

namespace App\Http\Controllers\API;

use App\Models\Invoice;
use App\Models\Payment;
use App\Helpers\ApiResponse;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StorePaymentRequest;

class PaymentController extends Controller
{
    /**
     * Display a listing of payments with advanced filters
     */
    public function index(Request $request)
    {
        try {
            $query = Payment::query();

            // Optimize: Select only needed columns
            $query->select('payments.*');

            // Eager load relationships to prevent N+1
            $query->with([
                'invoice:id,invoice_number,student_id,academic_year_id,total_amount,paid_amount,status',
                'invoice.student:id,nis,full_name',
                'invoice.academicYear:id,name',
                'processedBy:id,full_name'
            ]);

            // Filter by payment method
            if ($request->has('payment_method')) {
                $query->where('payment_method', $request->payment_method);
            }

            // Filter by date range
            if ($request->has('date_from')) {
                $query->whereDate('payment_date', '>=', $request->date_from);
            }
            if ($request->has('date_to')) {
                $query->whereDate('payment_date', '<=', $request->date_to);
            }

            // Filter by specific date
            if ($request->has('date')) {
                $query->whereDate('payment_date', $request->date);
            }

            // Filter by student
            if ($request->has('student_id')) {
                $query->whereHas('invoice', function ($q) use ($request) {
                    $q->where('student_id', $request->student_id);
                });
            }

            // Filter by academic year
            if ($request->has('academic_year_id')) {
                $query->whereHas('invoice', function ($q) use ($request) {
                    $q->where('academic_year_id', $request->academic_year_id);
                });
            }

            // Filter by processed by (admin)
            if ($request->has('processed_by')) {
                $query->where('processed_by', $request->processed_by);
            }

            // Filter by amount range
            if ($request->has('amount_min')) {
                $query->where('amount', '>=', $request->amount_min);
            }
            if ($request->has('amount_max')) {
                $query->where('amount', '<=', $request->amount_max);
            }

            // Search by receipt number or student name or NIS
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('receipt_number', 'like', "%{$search}%")
                      ->orWhereHas('invoice.student', function ($sq) use ($search) {
                          $sq->where('full_name', 'like', "%{$search}%")
                            ->orWhere('nis', 'like', "%{$search}%");
                      });
                });
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'payment_date');
            $sortOrder = $request->get('sort_order', 'desc');

            // Validate sort column
            $allowedSorts = ['payment_date', 'amount', 'payment_method', 'receipt_number', 'created_at'];
            if (in_array($sortBy, $allowedSorts)) {
                $query->orderBy($sortBy, $sortOrder);
            } else {
                $query->orderBy('payment_date', 'desc');
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $payments = $query->paginate($perPage);

            return ApiResponse::success($payments, 'List of payments');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to fetch payments: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Store a newly created payment (Record Pembayaran)
     */
    public function store(StorePaymentRequest $request)
    {
        try {
            DB::beginTransaction();

            $validated = $request->validated();

            // Get invoice
            $invoice = Invoice::with(['student', 'items.feeCategory'])->findOrFail($validated['invoice_id']);

            // Calculate remaining amount
            $remainingAmount = $invoice->total_amount - $invoice->paid_amount;

            // Validate: amount should not exceed remaining (allow overpayment based on your requirement)
            // If overpayment is allowed, we still record it but cap the invoice paid_amount at total_amount
            $paymentAmount = $validated['amount'];

            // Generate receipt number
            $receiptNumber = $this->generateReceiptNumber();

            // Create payment record
            $payment = Payment::create([
                'receipt_number' => $receiptNumber,
                'amount' => $paymentAmount,
                'payment_date' => $validated['payment_date'],
                'payment_method' => $validated['payment_method'],
                'notes' => $validated['notes'] ?? null,
                'invoice_id' => $invoice->id,
                'processed_by' => Auth::id(),
            ]);

            // Update invoice paid_amount
            $newPaidAmount = $invoice->paid_amount + $paymentAmount;

            // Cap at total_amount (prevent paid_amount > total_amount)
            if ($newPaidAmount > $invoice->total_amount) {
                $newPaidAmount = $invoice->total_amount;
            }

            $invoice->paid_amount = $newPaidAmount;

            // Update invoice status
            if ($newPaidAmount >= $invoice->total_amount) {
                $invoice->status = 'PAID';
            } elseif ($newPaidAmount > 0) {
                $invoice->status = 'PARTIAL';
            } else {
                $invoice->status = 'UNPAID';
            }

            $invoice->save();

            // Create notification for student
            $this->createPaymentNotification($invoice, $payment);

            DB::commit();

            return ApiResponse::success([
                'payment' => [
                    'id' => $payment->id,
                    'receipt_number' => $receiptNumber,
                    'amount' => $paymentAmount,
                    'payment_method' => $payment->payment_method,
                    'payment_date' => $payment->payment_date,
                ],
                'invoice' => [
                    'invoice_number' => $invoice->invoice_number,
                    'student_name' => $invoice->student->full_name,
                    'total_amount' => $invoice->total_amount,
                    'paid_amount' => $invoice->paid_amount,
                    'remaining_amount' => $invoice->total_amount - $invoice->paid_amount,
                    'status' => $invoice->status,
                ],
            ], 'Payment recorded successfully', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::error('Failed to record payment: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified payment with details
     */
    public function show($id)
    {
        try {
            $payment = Payment::with([
                'invoice.student',
                'invoice.academicYear',
                'invoice.items.feeCategory',
                'processedBy'
            ])->findOrFail($id);

            return ApiResponse::success($payment, 'Payment detail fetched');
        } catch (\Exception $e) {
            return ApiResponse::error('Payment not found: ' . $e->getMessage(), 404);
        }
    }

    /**
     * Get payment history for a specific student
     */
    public function studentHistory(Request $request, $studentId)
    {
        try {
            $query = Payment::with(['invoice.student', 'invoice.items.feeCategory', 'processedBy'])
                ->whereHas('invoice', function ($q) use ($studentId) {
                    $q->where('student_id', $studentId);
                });

            // Filter by date range if provided
            if ($request->has('date_from')) {
                $query->whereDate('payment_date', '>=', $request->date_from);
            }
            if ($request->has('date_to')) {
                $query->whereDate('payment_date', '<=', $request->date_to);
            }

            // Filter by academic year
            if ($request->has('academic_year_id')) {
                $query->whereHas('invoice', function ($q) use ($request) {
                    $q->where('academic_year_id', $request->academic_year_id);
                });
            }

            $perPage = $request->get('per_page', 15);
            $payments = $query->orderBy('payment_date', 'desc')->paginate($perPage);

            // Calculate summary
            $totalPaid = Payment::whereHas('invoice', function ($q) use ($studentId) {
                $q->where('student_id', $studentId);
            })->sum('amount');

            return ApiResponse::success([
                'summary' => [
                    'total_paid' => (float) $totalPaid,
                    'payment_count' => $payments->total(),
                ],
                'payments' => $payments,
            ], 'Payment history fetched');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to fetch payment history: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get payment history for current logged in student (Wali Murid)
     */
    public function myPayments(Request $request)
    {
        try {
            $user = Auth::user();

            // Check if user has student_id (is a student/wali murid)
            if (!$user->student_id) {
                return ApiResponse::error('User is not associated with a student', 403);
            }

            return $this->studentHistory($request, $user->student_id);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to fetch payment history: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Generate unique receipt number
     * Format: RCP/YYYY/MM/XXXX
     */
    private function generateReceiptNumber()
    {
        $year = date('Y');
        $month = date('m');
        $prefix = "RCP/{$year}/{$month}/";

        // Get last receipt number for this month
        $lastPayment = Payment::where('receipt_number', 'like', $prefix . '%')
            ->orderBy('receipt_number', 'desc')
            ->first();

        if ($lastPayment) {
            $lastNumber = (int) substr($lastPayment->receipt_number, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return $prefix . $newNumber;
    }

    /**
     * Create notification for payment success
     */
    private function createPaymentNotification($invoice, $payment)
    {
        try {
            // Get user associated with student
            $student = $invoice->student;
            if ($student->user) {
                Notification::create([
                    'user_id' => $student->user->id,
                    'title' => 'Pembayaran Berhasil',
                    'message' => "Pembayaran sebesar Rp " . number_format($payment->amount, 0, ',', '.') . " untuk invoice {$invoice->invoice_number} telah berhasil dicatat.",
                    'type' => 'PAYMENT_SUCCESS',
                    'is_read' => false,
                ]);
            }
        } catch (\Exception $e) {
            // Don't fail the payment if notification creation fails
            Log::error('Failed to create payment notification: ' . $e->getMessage());
        }
    }
}
