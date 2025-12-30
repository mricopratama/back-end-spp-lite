<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\InvoiceItem;
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
            $query = InvoiceItem::with([
                'student.currentClass',
                'student.classHistory.class',
                'feeCategory'
            ])
                ->whereIn('status', ['unpaid', 'partial']);

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

            $invoiceItems = $query->get();

            // Group by student
            $arrearsData = [];
            foreach ($invoiceItems as $item) {
                $studentId = $item->student_id;
                if (!isset($arrearsData[$studentId])) {
                    $currentClass = $item->student->classHistory()
                        ->with('class')
                        ->orderBy('academic_year_id', 'desc')
                        ->first();
                    $arrearsData[$studentId] = [
                        'student_id' => $item->student->id,
                        'student_name' => $item->student->full_name,
                        'student_nis' => $item->student->nis,
                        'class' => $currentClass?->class->name ?? 'N/A',
                        'total_debt' => 0,
                        'invoices_count' => 0,
                        'details' => [],
                    ];
                }
                $remainingAmount = $item->amount - $item->paid_amount;
                $arrearsData[$studentId]['total_debt'] += $remainingAmount;
                $arrearsData[$studentId]['invoices_count']++;
                $arrearsData[$studentId]['details'][] = [
                    'invoice_item_id' => $item->id,
                    'fee_category' => $item->feeCategory->name,
                    'description' => $item->description,
                    'amount' => $item->amount,
                    'paid_amount' => $item->paid_amount,
                    'remaining_amount' => $remainingAmount,
                    'due_date' => $item->due_date,
                    'status' => $item->status,
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
            $totalIncome = Payment::whereBetween('payment_date', [$startDate, $endDate])
                ->sum('amount');

            // Breakdown by payment method
            $breakdownByMethod = Payment::whereBetween('payment_date', [$startDate, $endDate])
                ->select('payment_method', DB::raw('SUM(amount) as total'))
                ->groupBy('payment_method')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->payment_method => (float) $item->total];
                });

            // Daily income
            $dailyIncome = Payment::whereBetween('payment_date', [$startDate, $endDate])
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

            // Breakdown by fee category (using invoice_items directly)
            $breakdownByCategory = Payment::whereBetween('payment_date', [$startDate, $endDate])
                ->join('invoice_items', 'payments.invoice_item_id', '=', 'invoice_items.id')
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
            $transactionCount = Payment::whereBetween('payment_date', [$startDate, $endDate])
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

            $query = InvoiceItem::with(['student', 'academicYear', 'feeCategory']);
            if ($academicYearId) {
                $query->where('academic_year_id', $academicYearId);
            }
            $invoiceItems = $query->get();

            // Calculate totals
            $totalExpected = $invoiceItems->sum('amount');
            $totalPaid = $invoiceItems->sum('paid_amount');
            $totalOutstanding = $totalExpected - $totalPaid;

            // Breakdown by status
            $byStatus = [
                'PAID' => 0,
                'PARTIAL' => 0,
                'UNPAID' => 0,
            ];
            foreach ($invoiceItems as $item) {
                $byStatus[strtoupper($item->status)] += $item->amount;
            }

            // Breakdown by fee category
            $byCategory = [];
            foreach ($invoiceItems as $item) {
                $categoryName = $item->feeCategory->name;
                if (!isset($byCategory[$categoryName])) {
                    $byCategory[$categoryName] = [
                        'expected' => 0,
                        'paid' => 0,
                        'outstanding' => 0,
                    ];
                }
                $byCategory[$categoryName]['expected'] += $item->amount;
                $byCategory[$categoryName]['paid'] += $item->paid_amount;
            }
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
                // Get invoice items for these students
                $invoiceItems = InvoiceItem::whereIn('student_id', $studentIds);
                if ($academicYearId) {
                    $invoiceItems->where('academic_year_id', $academicYearId);
                }
                $invoiceItems = $invoiceItems->get();
                $totalExpected = $invoiceItems->sum('amount');
                $totalPaid = $invoiceItems->sum('paid_amount');
                $totalOutstanding = $totalExpected - $totalPaid;
                $paidCount = $invoiceItems->where('status', 'PAID')->count();
                $unpaidCount = $invoiceItems->where('status', 'UNPAID')->count();
                $partialCount = $invoiceItems->where('status', 'PARTIAL')->count();
                $result[] = [
                    'class_id' => $class->id,
                    'class_name' => $class->name,
                    'student_count' => $studentIds->count(),
                    'total_expected' => (float) $totalExpected,
                    'total_paid' => (float) $totalPaid,
                    'total_outstanding' => (float) $totalOutstanding,
                    'collection_rate' => $totalExpected > 0 ? round(($totalPaid / $totalExpected) * 100, 2) : 0,
                    'invoice_items' => [
                        'paid' => $paidCount,
                        'partial' => $partialCount,
                        'unpaid' => $unpaidCount,
                        'total' => $invoiceItems->count(),
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
            $query = Payment::with(['invoiceItem.student', 'invoiceItem.feeCategory']);
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
                $query->whereHas('invoiceItem', function ($q) use ($request) {
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
