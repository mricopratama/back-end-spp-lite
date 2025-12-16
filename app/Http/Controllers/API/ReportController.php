<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Classes;
use App\Models\StudentClassHistory;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * Laporan Tunggakan (Arrears Report)
     * List siswa yang belum lunas dengan detail tunggakan
     */
    public function arrears(Request $request)
    {
        try {
            $query = Invoice::with([
                'student.currentClass',
                'student.classHistory.class',
                'items.feeCategory'
            ])
                ->whereIn('status', ['UNPAID', 'PARTIAL']);

            // Filter by class
            if ($request->has('class_id')) {
                $query->whereHas('student.classHistory', function ($q) use ($request) {
                    $q->where('class_id', $request->class_id);
                });
            }

            // Filter by academic year
            if ($request->has('academic_year_id')) {
                $query->where('academic_year_id', $request->academic_year_id);
            }

            $invoices = $query->get();

            // Group by student
            $arrearsData = [];
            foreach ($invoices as $invoice) {
                $studentId = $invoice->student_id;

                if (!isset($arrearsData[$studentId])) {
                    // Get current class
                    $currentClass = $invoice->student->classHistory()
                        ->with('class')
                        ->orderBy('academic_year_id', 'desc')
                        ->first();

                    $arrearsData[$studentId] = [
                        'student_id' => $invoice->student->id,
                        'student_name' => $invoice->student->full_name,
                        'student_nis' => $invoice->student->nis,
                        'class' => $currentClass?->class->name ?? 'N/A',
                        'total_debt' => 0,
                        'invoices_count' => 0,
                        'details' => [],
                    ];
                }

                $remainingAmount = $invoice->total_amount - $invoice->paid_amount;
                $arrearsData[$studentId]['total_debt'] += $remainingAmount;
                $arrearsData[$studentId]['invoices_count']++;

                // Build detail string
                $itemsDescription = $invoice->items->map(function ($item) {
                    return $item->description;
                })->join(', ');

                $arrearsData[$studentId]['details'][] = [
                    'invoice_number' => $invoice->invoice_number,
                    'description' => $itemsDescription,
                    'total_amount' => $invoice->total_amount,
                    'paid_amount' => $invoice->paid_amount,
                    'remaining_amount' => $remainingAmount,
                    'due_date' => $invoice->due_date,
                    'status' => $invoice->status,
                ];
            }

            // Convert to array and sort by total debt (descending)
            $result = array_values($arrearsData);
            usort($result, function ($a, $b) {
                return $b['total_debt'] <=> $a['total_debt'];
            });

            // Calculate summary
            $totalStudents = count($result);
            $totalDebt = array_sum(array_column($result, 'total_debt'));

            return ApiResponse::success([
                'summary' => [
                    'total_students_with_arrears' => $totalStudents,
                    'total_debt_amount' => (float) $totalDebt,
                ],
                'data' => $result,
            ], 'Arrears report fetched');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to fetch arrears report: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Laporan Pendapatan (Income Report)
     * Rekap uang masuk real dari pembayaran
     */
    public function income(Request $request)
    {
        try {
            // Default to current month if no date provided
            $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
            $endDate = $request->get('end_date', now()->endOfMonth()->format('Y-m-d'));

            // Total income in period
            $totalIncome = Payment::whereNull('deleted_at')
                ->whereBetween('payment_date', [$startDate, $endDate])
                ->sum('amount');

            // Breakdown by payment method
            $breakdownByMethod = Payment::whereNull('deleted_at')
                ->whereBetween('payment_date', [$startDate, $endDate])
                ->select('payment_method', DB::raw('SUM(amount) as total'))
                ->groupBy('payment_method')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->payment_method => (float) $item->total];
                });

            // Daily income
            $dailyIncome = Payment::whereNull('deleted_at')
                ->whereBetween('payment_date', [$startDate, $endDate])
                ->select(
                    DB::raw('DATE(payment_date) as date'),
                    DB::raw('SUM(amount) as amount'),
                    DB::raw('COUNT(*) as transaction_count')
                )
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->map(function ($item) {
                    return [
                        'date' => $item->date,
                        'amount' => (float) $item->amount,
                        'transaction_count' => $item->transaction_count,
                    ];
                });

            // Breakdown by fee category
            $breakdownByCategory = Payment::whereNull('deleted_at')
                ->whereBetween('payment_date', [$startDate, $endDate])
                ->join('invoices', 'payments.invoice_id', '=', 'invoices.id')
                ->join('invoice_items', 'invoices.id', '=', 'invoice_items.invoice_id')
                ->join('fee_categories', 'invoice_items.fee_category_id', '=', 'fee_categories.id')
                ->select('fee_categories.name', DB::raw('SUM(payments.amount) as total'))
                ->groupBy('fee_categories.id', 'fee_categories.name')
                ->get()
                ->map(function ($item) {
                    return [
                        'category' => $item->name,
                        'total' => (float) $item->total,
                    ];
                });

            // Transaction count
            $transactionCount = Payment::whereNull('deleted_at')
                ->whereBetween('payment_date', [$startDate, $endDate])
                ->count();

            return ApiResponse::success([
                'period' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ],
                'total_income' => (float) $totalIncome,
                'transaction_count' => $transactionCount,
                'breakdown_by_method' => $breakdownByMethod,
                'breakdown_by_category' => $breakdownByCategory,
                'daily_income' => $dailyIncome,
            ], 'Income report fetched');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to fetch income report: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Laporan Rencana Pemasukan (Expected Income Report)
     * Based on invoices (not actual payments)
     */
    public function expectedIncome(Request $request)
    {
        try {
            // Filter by academic year
            $academicYearId = $request->get('academic_year_id');

            $query = Invoice::with(['student', 'academicYear', 'items.feeCategory']);

            if ($academicYearId) {
                $query->where('academic_year_id', $academicYearId);
            }

            $invoices = $query->get();

            // Calculate totals
            $totalExpected = $invoices->sum('total_amount');
            $totalPaid = $invoices->sum('paid_amount');
            $totalOutstanding = $totalExpected - $totalPaid;

            // Breakdown by status
            $byStatus = [
                'PAID' => 0,
                'PARTIAL' => 0,
                'UNPAID' => 0,
            ];

            foreach ($invoices as $invoice) {
                $byStatus[$invoice->status] += $invoice->total_amount;
            }

            // Breakdown by fee category
            $byCategory = [];
            foreach ($invoices as $invoice) {
                foreach ($invoice->items as $item) {
                    $categoryName = $item->feeCategory->name;
                    if (!isset($byCategory[$categoryName])) {
                        $byCategory[$categoryName] = [
                            'expected' => 0,
                            'paid' => 0,
                            'outstanding' => 0,
                        ];
                    }
                    $byCategory[$categoryName]['expected'] += $item->amount;
                }
            }

            // Calculate paid amount per category from payments
            $payments = Payment::whereNull('deleted_at')
                ->whereIn('invoice_id', $invoices->pluck('id'))
                ->get();

            foreach ($payments as $payment) {
                $invoice = $invoices->firstWhere('id', $payment->invoice_id);
                if ($invoice) {
                    foreach ($invoice->items as $item) {
                        $categoryName = $item->feeCategory->name;
                        // Distribute payment proportionally
                        $proportion = $item->amount / $invoice->total_amount;
                        $byCategory[$categoryName]['paid'] += $payment->amount * $proportion;
                    }
                }
            }

            // Calculate outstanding per category
            foreach ($byCategory as $category => &$data) {
                $data['outstanding'] = $data['expected'] - $data['paid'];
                $data['expected'] = (float) $data['expected'];
                $data['paid'] = (float) $data['paid'];
                $data['outstanding'] = (float) $data['outstanding'];
            }

            return ApiResponse::success([
                'summary' => [
                    'total_expected' => (float) $totalExpected,
                    'total_paid' => (float) $totalPaid,
                    'total_outstanding' => (float) $totalOutstanding,
                    'collection_rate' => $totalExpected > 0 ? round(($totalPaid / $totalExpected) * 100, 2) : 0,
                ],
                'by_status' => [
                    'PAID' => (float) $byStatus['PAID'],
                    'PARTIAL' => (float) $byStatus['PARTIAL'],
                    'UNPAID' => (float) $byStatus['UNPAID'],
                ],
                'by_category' => $byCategory,
            ], 'Expected income report fetched');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to fetch expected income report: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Laporan per Kelas (Class Report)
     * Summary pembayaran per kelas
     */
    public function classReport(Request $request)
    {
        try {
            $academicYearId = $request->get('academic_year_id');

            $classes = Classes::all();
            $result = [];

            foreach ($classes as $class) {
                // Get students in this class
                $studentIds = StudentClassHistory::where('class_id', $class->id);

                if ($academicYearId) {
                    $studentIds->where('academic_year_id', $academicYearId);
                }

                $studentIds = $studentIds->pluck('student_id');

                if ($studentIds->isEmpty()) {
                    continue;
                }

                // Get invoices for these students
                $invoices = Invoice::whereIn('student_id', $studentIds);

                if ($academicYearId) {
                    $invoices->where('academic_year_id', $academicYearId);
                }

                $invoices = $invoices->get();

                $totalExpected = $invoices->sum('total_amount');
                $totalPaid = $invoices->sum('paid_amount');
                $totalOutstanding = $totalExpected - $totalPaid;

                $paidCount = $invoices->where('status', 'PAID')->count();
                $unpaidCount = $invoices->where('status', 'UNPAID')->count();
                $partialCount = $invoices->where('status', 'PARTIAL')->count();

                $result[] = [
                    'class_id' => $class->id,
                    'class_name' => $class->name,
                    'student_count' => $studentIds->count(),
                    'total_expected' => (float) $totalExpected,
                    'total_paid' => (float) $totalPaid,
                    'total_outstanding' => (float) $totalOutstanding,
                    'collection_rate' => $totalExpected > 0 ? round(($totalPaid / $totalExpected) * 100, 2) : 0,
                    'invoices' => [
                        'paid' => $paidCount,
                        'partial' => $partialCount,
                        'unpaid' => $unpaidCount,
                        'total' => $invoices->count(),
                    ],
                ];
            }

            // Sort by total outstanding (descending)
            usort($result, function ($a, $b) {
                return $b['total_outstanding'] <=> $a['total_outstanding'];
            });

            return ApiResponse::success($result, 'Class report fetched');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to fetch class report: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Laporan Pembayaran Detail (Payment History)
     * List semua pembayaran dengan detail
     */
    public function paymentHistory(Request $request)
    {
        try {
            $query = Payment::with(['invoice.student', 'invoice.items.feeCategory'])
                ->whereNull('deleted_at');

            // Filter by date range
            if ($request->has('start_date')) {
                $query->whereDate('payment_date', '>=', $request->start_date);
            }
            if ($request->has('end_date')) {
                $query->whereDate('payment_date', '<=', $request->end_date);
            }

            // Filter by payment method
            if ($request->has('payment_method')) {
                $query->where('payment_method', $request->payment_method);
            }

            // Filter by student
            if ($request->has('student_id')) {
                $query->whereHas('invoice', function ($q) use ($request) {
                    $q->where('student_id', $request->student_id);
                });
            }

            $payments = $query->orderBy('payment_date', 'desc')->paginate(20);

            return ApiResponse::success($payments, 'Payment history fetched');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to fetch payment history: ' . $e->getMessage(), 500);
        }
    }
}
