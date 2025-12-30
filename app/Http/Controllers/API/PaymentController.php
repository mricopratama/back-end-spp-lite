<?php

namespace App\Http\Controllers\API;

use App\Models\InvoiceItem;
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
            $user = Auth::user();

            $query = Payment::query();

            // Optimize: Select only needed columns
            $query->select('payments.*');

            // Eager load relationships to prevent N+1
                $query->with([
                    'invoiceItem:id,invoice_number,student_id,academic_year_id,total_amount,paid_amount,status',
                    'invoiceItem.student:id,nis,full_name',
                    'invoiceItem.academicYear:id,name',
                    'processedBy:id,full_name'
                ]);

            // If user is student, only return their own payments
            if ($user->role->name === 'student') {
                if (!$user->student_id) {
                    return ApiResponse::error('Student record not found', 404);
                }
                $query->whereHas('invoiceItem', function ($q) use ($user) {
                    $q->where('student_id', $user->student_id);
                });
            }

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
                $query->whereHas('invoiceItem', function ($q) use ($request) {
                    $q->where('student_id', $request->student_id);
                });
            }

            // Filter by academic year
            if ($request->has('academic_year_id')) {
                $query->whereHas('invoiceItem', function ($q) use ($request) {
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
                                            ->orWhereHas('invoiceItem.student', function ($sq) use ($search) {
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
            $invoiceItem = InvoiceItem::with(['student', 'feeCategory'])->findOrFail($validated['invoice_item_id']);

            // Calculate remaining amount
            $remainingAmount = $invoiceItem->amount - $invoiceItem->paid_amount;

            // Prevent overpayment
            if ($validated['amount'] > $remainingAmount) {
                return ApiResponse::error(
                    "Payment amount ({$validated['amount']}) exceeds remaining amount ({$remainingAmount}). Please adjust the payment amount.",
                    400
                );
            }

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
                'invoice_item_id' => $invoiceItem->id,
                'processed_by' => Auth::id(),
            ]);

            // Update invoice paid_amount
            $newPaidAmount = $invoiceItem->paid_amount + $paymentAmount;
            $invoiceItem->paid_amount = $newPaidAmount;
            // Update status
            if ($newPaidAmount >= $invoiceItem->amount) {
                $invoiceItem->status = 'paid';
            } elseif ($newPaidAmount > 0) {
                $invoiceItem->status = 'partial';
            } else {
                $invoiceItem->status = 'unpaid';
            }
            $invoiceItem->save();

            // Create notification for student
            $this->createPaymentNotification($invoiceItem, $payment);

            DB::commit();

            return ApiResponse::success([
                'payment' => [
                    'id' => $payment->id,
                    'receipt_number' => $receiptNumber,
                    'amount' => $paymentAmount,
                    'payment_method' => $payment->payment_method,
                    'payment_date' => $payment->payment_date,
                ],
                'invoice_item' => [
                    'id' => $invoiceItem->id,
                    'student_name' => $invoiceItem->student->full_name,
                    'amount' => $invoiceItem->amount,
                    'paid_amount' => $invoiceItem->paid_amount,
                    'remaining_amount' => $invoiceItem->amount - $invoiceItem->paid_amount,
                    'status' => $invoiceItem->status,
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
            $user = Auth::user();

            $payment = Payment::with([
                'invoiceItem.student',
                'invoiceItem.academicYear',
                'invoiceItem.feeCategory',
                'processedBy'
            ])->findOrFail($id);

            // If user is student, only allow access to their own payments
            if ($user->role->name === 'student') {
                if ($payment->invoiceItem->student_id != $user->student_id) {
                    return ApiResponse::error('Forbidden: You can only access your own payments', 403);
                }
            }

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
            $user = Auth::user();

            // If user is student, only allow access to their own payment history
            if ($user->role->name === 'student') {
                if ($user->student_id != $studentId) {
                    return ApiResponse::error('Forbidden: You can only access your own payment history', 403);
                }
            }

            $query = Payment::with(['invoiceItem.student', 'invoiceItem.feeCategory', 'processedBy'])
                ->whereHas('invoiceItem', function ($q) use ($studentId) {
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
                $query->whereHas('invoiceItem', function ($q) use ($request) {
                    $q->where('academic_year_id', $request->academic_year_id);
                });
            }

            $perPage = $request->get('per_page', 15);
            $payments = $query->orderBy('payment_date', 'desc')->paginate($perPage);

            // Calculate summary
            $totalPaid = Payment::whereHas('invoiceItem', function ($q) use ($studentId) {
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
     * Get payment history with date range filter (Admin)
     */
    public function paymentHistory(Request $request)
    {
        try {
            $query = Payment::query();
            $query->with([
                'invoiceItem:id,student_id,academic_year_id,amount,paid_amount,status',
                'invoiceItem.student:id,nis,full_name',
                'invoiceItem.academicYear:id,name',
                'processedBy:id,full_name'
            ]);

            // Required date range filter
            if ($request->has('start_date') && $request->has('end_date')) {
                $query->whereDate('payment_date', '>=', $request->start_date)
                      ->whereDate('payment_date', '<=', $request->end_date);
            }

            // Optional filters
            if ($request->has('payment_method')) {
                $query->where('payment_method', $request->payment_method);
            }

            if ($request->has('student_id')) {
                $query->whereHas('invoiceItem', function ($q) use ($request) {
                    $q->where('student_id', $request->student_id);
                });
            }

            if ($request->has('academic_year_id')) {
                $query->whereHas('invoiceItem', function ($q) use ($request) {
                    $q->where('academic_year_id', $request->academic_year_id);
                });
            }

            $query->orderBy('payment_date', 'desc');

            // Check pagination
            if ($request->get('paginate', true) === 'false' || $request->get('paginate') === false) {
                $payments = $query->get();

                return ApiResponse::success([
                    'payments' => $payments,
                    'summary' => [
                        'total_payments' => $payments->count(),
                        'total_amount' => $payments->sum('amount'),
                    ],
                ], 'Payment history');
            }

            $perPage = $request->get('per_page', 15);
            $payments = $query->paginate($perPage);

            // Calculate total for all matching records (not just current page)
            $totalAmount = Payment::query()
                ->when($request->has('start_date') && $request->has('end_date'), function ($q) use ($request) {
                    $q->whereDate('payment_date', '>=', $request->start_date)
                      ->whereDate('payment_date', '<=', $request->end_date);
                })
                ->when($request->has('payment_method'), function ($q) use ($request) {
                    $q->where('payment_method', $request->payment_method);
                })
                ->when($request->has('student_id'), function ($q) use ($request) {
                    $q->whereHas('invoiceItem', function ($query) use ($request) {
                        $query->where('student_id', $request->student_id);
                    });
                })
                ->sum('amount');

            return ApiResponse::success([
                'payments' => $payments,
                'summary' => [
                    'total_payments' => $payments->total(),
                    'total_amount' => $totalAmount,
                ],
            ], 'Payment history');
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
    private function createPaymentNotification($invoiceItem, $payment)
    {
        try {
            // Get user associated with student
            $student = $invoiceItem->student;
            if ($student->user) {
                Notification::create([
                    'user_id' => $student->user->id,
                    'title' => 'Pembayaran Berhasil',
                    'message' => "Pembayaran sebesar Rp " . number_format($payment->amount, 0, ',', '.') . " untuk invoice item #{$invoiceItem->id} telah berhasil dicatat.",
                    'type' => 'PAYMENT_SUCCESS',
                    'is_read' => false,
                ]);
            }
        } catch (\Exception $e) {
            // Don't fail the payment if notification creation fails
            Log::error('Failed to create payment notification: ' . $e->getMessage());
        }
    }

    /**
     * Print payment receipt
     */
    public function printReceipt(Payment $payment)
    {
        $user = Auth::user();

        // If user is student, only allow access to their own receipts
        if ($user->role->name === 'student') {
            if ($payment->invoiceItem->student_id != $user->student_id) {
                return ApiResponse::error('Forbidden: You can only print your own receipts', 403);
            }
        }

        $payment->load([
            'invoiceItem.student',
            'invoiceItem.academicYear',
            'invoiceItem.feeCategory',
            'processedBy'
        ]);

        return ApiResponse::success([
            'receipt' => [
                'receipt_number' => $payment->receipt_number,
                'payment_date' => $payment->payment_date->format('d/m/Y'),
                'payment_time' => $payment->created_at->format('H:i:s'),
                'amount' => $payment->amount,
                'amount_formatted' => 'Rp ' . number_format((float)($payment->amount ?? 0), 0, ',', '.'),
                'payment_method' => $payment->payment_method,
                'notes' => $payment->notes,
            ],
            'student' => [
                'nis' => $payment->invoiceItem->student->nis,
                'name' => $payment->invoiceItem->student->full_name,
                'address' => $payment->invoiceItem->student->address,
            ],
            'invoice_item' => [
                'id' => $payment->invoiceItem->id,
                'academic_year' => $payment->invoiceItem->academicYear->name,
                'amount' => $payment->invoiceItem->amount,
                'paid_amount' => $payment->invoiceItem->paid_amount,
                'remaining_amount' => $payment->invoiceItem->amount - $payment->invoiceItem->paid_amount,
                'status' => $payment->invoiceItem->status,
                'fee_category' => $payment->invoiceItem->feeCategory->name,
            ],
            'processed_by' => $payment->processedBy ? $payment->processedBy->full_name : null,
            'print_date' => now()->format('d/m/Y H:i:s'),
        ], 'Payment receipt data');
    }

    /**
     * Delete payment and update invoice
     */
    public function destroy(Payment $payment)
    {
        try {
            DB::beginTransaction();

            // Get invoice before deleting payment
            $invoiceItem = InvoiceItem::findOrFail($payment->invoice_item_id);

            // Store payment amount for invoice update
            $paymentAmount = $payment->amount;
            // Delete payment
            $payment->delete();
            // Update invoice item paid_amount
            $invoiceItem->paid_amount = $invoiceItem->paid_amount - $paymentAmount;
            if ($invoiceItem->paid_amount < 0) {
                $invoiceItem->paid_amount = 0;
            }
            // Update status
            if ($invoiceItem->paid_amount >= $invoiceItem->amount) {
                $invoiceItem->status = 'paid';
            } elseif ($invoiceItem->paid_amount > 0) {
                $invoiceItem->status = 'partial';
            } else {
                $invoiceItem->status = 'unpaid';
            }
            $invoiceItem->save();

            DB::commit();

            return ApiResponse::success([
                'invoice_item' => [
                    'id' => $invoiceItem->id,
                    'amount' => $invoiceItem->amount,
                    'paid_amount' => $invoiceItem->paid_amount,
                    'remaining_amount' => $invoiceItem->amount - $invoiceItem->paid_amount,
                    'status' => $invoiceItem->status,
                ],
            ], 'Payment deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::error('Failed to delete payment: ' . $e->getMessage(), 500);
        }
    }
}
