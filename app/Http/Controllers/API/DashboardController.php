<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Admin Dashboard Statistics
     * - Total income today & this month
     * - Active students count
     * - Paid invoice percentage
     * - Recent payments
     */
    public function adminStats(Request $request)
    {
        try {
            $today = now()->format('Y-m-d');
            $monthStart = now()->startOfMonth()->format('Y-m-d');
            $monthEnd = now()->endOfMonth()->format('Y-m-d');

            // Total income today (from payments)
            $totalIncomeToday = Payment::whereDate('payment_date', $today)
                ->sum('amount');

            // Total income this month
            $totalIncomeMonth = Payment::whereBetween('payment_date', [$monthStart, $monthEnd])
                ->sum('amount');

            // Active students count
            $activeStudentsCount = Student::where('status', 'ACTIVE')->count();

            // Paid invoice percentage
            $totalInvoices = InvoiceItem::count();
            $paidInvoices = InvoiceItem::where('status', 'PAID')->count();
            $paidPercentage = $totalInvoices > 0 ? round(($paidInvoices / $totalInvoices) * 100, 2) : 0;

            // Total outstanding amount (unpaid + partial)
            $totalOutstanding = InvoiceItem::whereIn('status', ['UNPAID', 'PARTIAL'])
                ->sum(DB::raw('amount - paid_amount'));

            // Recent payments (last 10)
            $recentPayments = Payment::with(['invoice.student'])
                ->orderBy('payment_date', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($payment) {
                    return [
                        'student' => $payment->invoice->student->full_name,
                        'amount' => $payment->amount,
                        'method' => $payment->payment_method,
                        'time' => $payment->payment_date->format('H:i'),
                        'date' => $payment->payment_date->format('Y-m-d'),
                    ];
                });

            // Unpaid invoices count
            $unpaidInvoicesCount = InvoiceItem::where('status', 'UNPAID')->count();

            // Partial invoices count
            $partialInvoicesCount = InvoiceItem::where('status', 'PARTIAL')->count();

            return ApiResponse::success([
                'total_income_today' => (float) $totalIncomeToday,
                'total_income_month' => (float) $totalIncomeMonth,
                'active_students_count' => $activeStudentsCount,
                'paid_invoice_percentage' => $paidPercentage,
                'total_outstanding' => (float) $totalOutstanding,
                'unpaid_invoices_count' => $unpaidInvoicesCount,
                'partial_invoices_count' => $partialInvoicesCount,
                'recent_payments' => $recentPayments,
            ], 'Admin dashboard statistics fetched');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to fetch dashboard stats: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Student Dashboard Statistics
     * - Student name & class
     * - Total outstanding amount (unpaid + partial)
     * - Unpaid invoices count
     * - Last payment date
     * - Recent invoices
     */
    public function studentStats(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user->student_id) {
                return ApiResponse::error('Not authorized as student', 403);
            }

            $student = Student::with(['currentClass', 'classHistory.academicYear', 'classHistory.class'])
                ->findOrFail($user->student_id);

            // Total outstanding amount
            $totalOutstanding = InvoiceItem::where('student_id', $student->id)
                ->whereIn('status', ['UNPAID', 'PARTIAL'])
                ->sum(DB::raw('total_amount - paid_amount'));

            // Unpaid invoices count
            $unpaidInvoicesCount = InvoiceItem::where('student_id', $student->id)
                ->where('status', 'UNPAID')
                ->count();

            // Partial invoices count
            $partialInvoicesCount = InvoiceItem::where('student_id', $student->id)
                ->where('status', 'PARTIAL')
                ->count();

            // Last payment date
            $lastPayment = Payment::whereHas('invoice', function ($query) use ($student) {
                $query->where('student_id', $student->id);
            })
                ->orderBy('payment_date', 'desc')
                ->first();

            // Recent invoices (last 5)
            $recentInvoices = InvoiceItem::where('student_id', $student->id)
                ->with(['feeCategory', 'academicYear'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($invoice) {
                    return [
                        'id' => $invoice->id,
                        'invoice_number' => $invoice->invoice_number,
                        'total_amount' => $invoice->total_amount,
                        'paid_amount' => $invoice->paid_amount,
                        'remaining_amount' => $invoice->total_amount - $invoice->paid_amount,
                        'status' => $invoice->status,
                        'due_date' => $invoice->due_date,
                        'items_count' => $invoice->items->count(),
                    ];
                });

            // Get current class info
            $currentClassHistory = $student->classHistory()
                ->with(['class', 'academicYear'])
                ->orderBy('academic_year_id', 'desc')
                ->first();

            return ApiResponse::success([
                'student_name' => $student->full_name,
                'student_nis' => $student->nis,
                'class_name' => $currentClassHistory?->class->name,
                'academic_year' => $currentClassHistory?->academicYear->name,
                'total_outstanding_amount' => (float) $totalOutstanding,
                'unpaid_invoices_count' => $unpaidInvoicesCount,
                'partial_invoices_count' => $partialInvoicesCount,
                'last_payment_date' => $lastPayment ? $lastPayment->payment_date->format('Y-m-d') : null,
                'last_payment_amount' => $lastPayment ? (float) $lastPayment->amount : null,
                'recent_invoices' => $recentInvoices,
            ], 'Student dashboard statistics fetched');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to fetch dashboard stats: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Monthly income summary (for charts)
     * Returns income per month for the current year
     */
    public function monthlyIncome(Request $request)
    {
        try {
            $year = $request->get('year', date('Y'));

            $monthlyData = Payment::whereYear('payment_date', $year)
                ->select(
                    DB::raw('MONTH(payment_date) as month'),
                    DB::raw('SUM(amount) as total')
                )
                ->groupBy('month')
                ->orderBy('month')
                ->get();

            // Fill missing months with 0
            $result = [];
            for ($i = 1; $i <= 12; $i++) {
                $monthData = $monthlyData->firstWhere('month', $i);
                $result[] = [
                    'month' => $i,
                    'month_name' => date('F', mktime(0, 0, 0, $i, 1)),
                    'total' => $monthData ? (float) $monthData->total : 0,
                ];
            }

            return ApiResponse::success([
                'year' => $year,
                'monthly_income' => $result,
            ], 'Monthly income summary fetched');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to fetch monthly income: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Payment method breakdown
     * Returns total amount by payment method
     */
    public function paymentMethodBreakdown(Request $request)
    {
        try {
            $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
            $endDate = $request->get('end_date', now()->endOfMonth()->format('Y-m-d'));

            $breakdown = Payment::whereBetween('payment_date', [$startDate, $endDate])
                ->select('payment_method', DB::raw('SUM(amount) as total'))
                ->groupBy('payment_method')
                ->get()
                ->map(function ($item) {
                    return [
                        'method' => $item->payment_method,
                        'total' => (float) $item->total,
                    ];
                });

            $totalAmount = $breakdown->sum('total');

            return ApiResponse::success([
                'period' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ],
                'total_amount' => $totalAmount,
                'breakdown' => $breakdown,
            ], 'Payment method breakdown fetched');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to fetch payment breakdown: ' . $e->getMessage(), 500);
        }
    }
}
