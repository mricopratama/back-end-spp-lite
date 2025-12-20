<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Student;
use App\Models\FeeCategory;
use App\Models\StudentClassHistory;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\BulkInvoiceRequest;
use App\Http\Requests\ImportInvoicesRequest;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\IOFactory;

class InvoiceController extends Controller
{
    /**
     * Display a listing of invoices
     */
    public function index(Request $request)
    {
        try {
            $query = Invoice::with(['student', 'academicYear', 'items.feeCategory']);

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by student
            if ($request->has('student_id')) {
                $query->where('student_id', $request->student_id);
            }

            // Filter by academic year
            if ($request->has('academic_year_id')) {
                $query->where('academic_year_id', $request->academic_year_id);
            }

            // Filter by due date range
            if ($request->has('due_date_from')) {
                $query->where('due_date', '>=', $request->due_date_from);
            }
            if ($request->has('due_date_to')) {
                $query->where('due_date', '<=', $request->due_date_to);
            }

            // Search by invoice number or student name
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('invoice_number', 'like', "%{$search}%")
                      ->orWhereHas('student', function ($sq) use ($search) {
                          $sq->where('full_name', 'like', "%{$search}%");
                      });
                });
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $invoices = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return ApiResponse::success($invoices, 'List of invoices');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to fetch invoices: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Store a newly created invoice (single student)
     */
    public function store(StoreInvoiceRequest $request)
    {
        try {
            DB::beginTransaction();

            $validated = $request->validated();

            // Generate invoice number
            $invoiceNumber = $this->generateInvoiceNumber();

            // Calculate total amount
            $totalAmount = 0;
            foreach ($validated['items'] as $item) {
                // Get fee category default amount if custom amount not provided
                if (!isset($item['custom_amount'])) {
                    $feeCategory = FeeCategory::findOrFail($item['fee_category_id']);
                    $item['custom_amount'] = $feeCategory->default_amount;
                }
                $totalAmount += $item['custom_amount'];
            }

            // Create invoice
            $invoice = Invoice::create([
                'invoice_number' => $invoiceNumber,
                'title' => $validated['title'] ?? 'Invoice Tagihan SPP',
                'student_id' => $validated['student_id'],
                'academic_year_id' => $validated['academic_year_id'],
                'total_amount' => $totalAmount,
                'paid_amount' => 0,
                'status' => 'unpaid',
                'due_date' => $validated['due_date'],
            ]);

            // Create invoice items
            foreach ($validated['items'] as $item) {
                if (!isset($item['custom_amount'])) {
                    $feeCategory = FeeCategory::findOrFail($item['fee_category_id']);
                    $item['custom_amount'] = $feeCategory->default_amount;
                }

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'fee_category_id' => $item['fee_category_id'],
                    'description' => $item['description'],
                    'amount' => $item['custom_amount'],
                ]);
            }

            DB::commit();

            $student = Student::find($validated['student_id']);

            return ApiResponse::success([
                'id' => $invoice->id,
                'invoice_number' => $invoiceNumber,
                'student_name' => $student->full_name,
                'total_amount' => $totalAmount,
                'status' => 'unpaid',
                'items_count' => count($validated['items']),
            ], 'Invoice generated successfully', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::error('Failed to create invoice: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Create invoices for multiple students (bulk)
     */
    public function bulkStore(BulkInvoiceRequest $request)
    {
        try {
            DB::beginTransaction();

            $validated = $request->validated();
            $studentIds = [];

            // Get student IDs based on class_id or student_ids
            if (isset($validated['class_id'])) {
                $studentIds = StudentClassHistory::where([
                    'class_id' => $validated['class_id'],
                    'academic_year_id' => $validated['academic_year_id'],
                ])->pluck('student_id')->toArray();
            } elseif (isset($validated['student_ids'])) {
                $studentIds = $validated['student_ids'];
            }

            if (empty($studentIds)) {
                return ApiResponse::error('No students found', 400);
            }

            $created = 0;
            $skipped = 0;

            foreach ($studentIds as $studentId) {
                // Check if student already has invoice for this period
                // (You can customize this check based on your business logic)
                $existingInvoice = Invoice::where([
                    'student_id' => $studentId,
                    'academic_year_id' => $validated['academic_year_id'],
                ])->whereHas('items', function ($q) use ($validated) {
                    foreach ($validated['items'] as $item) {
                        $q->where('fee_category_id', $item['fee_category_id']);
                    }
                })->exists();

                if ($existingInvoice) {
                    $skipped++;
                    continue;
                }

                // Generate invoice number
                $invoiceNumber = $this->generateInvoiceNumber();

                // Calculate total amount
                $totalAmount = 0;
                foreach ($validated['items'] as $item) {
                    if (!isset($item['custom_amount'])) {
                        $feeCategory = FeeCategory::findOrFail($item['fee_category_id']);
                        $item['custom_amount'] = $feeCategory->default_amount;
                    }
                    $totalAmount += $item['custom_amount'];
                }

                // Create invoice
                $invoice = Invoice::create([
                    'invoice_number' => $invoiceNumber,
                    'title' => $validated['title'] ?? 'Invoice Tagihan SPP',
                    'student_id' => $studentId,
                    'academic_year_id' => $validated['academic_year_id'],
                    'total_amount' => $totalAmount,
                    'paid_amount' => 0,
                    'status' => 'unpaid',
                    'due_date' => $validated['due_date'],
                ]);

                // Create invoice items
                foreach ($validated['items'] as $item) {
                    if (!isset($item['custom_amount'])) {
                        $feeCategory = FeeCategory::findOrFail($item['fee_category_id']);
                        $item['custom_amount'] = $feeCategory->default_amount;
                    }

                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'fee_category_id' => $item['fee_category_id'],
                        'description' => $item['description'],
                        'amount' => $item['custom_amount'],
                    ]);
                }

                $created++;
            }

            DB::commit();

            return ApiResponse::success([
                'processed_count' => $created,
                'skipped_count' => $skipped,
                'total_students' => count($studentIds),
            ], 'Bulk invoice generation completed');
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::error('Failed to create bulk invoices: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified invoice with details
     */
    public function show(Invoice $invoice)
    {
        $invoice->load([
            'student',
            'academicYear',
            'items.feeCategory',
            'payments'
        ]);

        return ApiResponse::success([
            'id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'title' => $invoice->title,
            'student_name' => $invoice->student->full_name,
            'student_nis' => $invoice->student->nis,
            'academic_year' => $invoice->academicYear->name,
            'status' => $invoice->status,
            'total_amount' => $invoice->total_amount,
            'paid_amount' => $invoice->paid_amount,
            'remaining_amount' => $invoice->total_amount - $invoice->paid_amount,
            'due_date' => $invoice->due_date->format('Y-m-d'),
            'items' => $invoice->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'fee_category' => $item->feeCategory->name,
                    'description' => $item->description,
                    'amount' => $item->amount,
                ];
            }),
            'payments' => $invoice->payments->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'amount' => $payment->amount,
                    'payment_method' => $payment->payment_method,
                    'payment_date' => $payment->payment_date,
                    'notes' => $payment->notes,
                ];
            }),
        ], 'Invoice detail fetched');
    }

    /**
     * Get my invoices (for student)
     */
    public function myInvoices(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user->student_id) {
                return ApiResponse::error('Not authorized as student', 403);
            }

            $query = Invoice::with(['academicYear', 'items.feeCategory'])
                ->where('student_id', $user->student_id);

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            $invoices = $query->orderBy('due_date', 'desc')->paginate(15);

            return ApiResponse::success($invoices, 'My invoices fetched');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to fetch invoices: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified invoice (void)
     */
    public function destroy(Invoice $invoice)
    {
        try {
            DB::beginTransaction();

            // Check if invoice has payments
            if ($invoice->paid_amount > 0) {
                return ApiResponse::error(
                    'Cannot delete invoice with existing payments. Paid amount must be 0.',
                    400
                );
            }

            // Delete invoice items
            $invoice->items()->delete();

            // Delete invoice
            $invoice->delete();

            DB::commit();

            return ApiResponse::success(null, 'Invoice deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::error('Failed to delete invoice: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Import invoices from Excel
     * Format: NIS, Nama_Siswa, Kelas, Fee_Category, Jumlah, Jatuh_Tempo, Bulan, Tahun_Ajaran
     */
    public function import(ImportInvoicesRequest $request)
    {
        try {
            DB::beginTransaction();

            $validated = $request->validated();
            $file = $request->file('file');
            $spreadsheet = IOFactory::load($file->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            // Remove header row
            $header = array_shift($rows);

            $created = 0;
            $failed = 0;
            $errors = [];

            // Group by student and due date
            $groupedData = [];

            foreach ($rows as $index => $row) {
                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }

                $rowNumber = $index + 2;

                try {
                    // Validate required fields
                    if (empty($row[0]) || empty($row[3]) || empty($row[4])) {
                        $errors[] = "Row {$rowNumber}: NIS, Fee_Category, dan Jumlah wajib diisi";
                        $failed++;
                        continue;
                    }

                    $nis = $row[0];
                    $feeCategoryName = $row[3];
                    $amount = $row[4];
                    $dueDate = $row[5] ?? now()->addDays(30)->format('Y-m-d');
                    $month = $row[6] ?? null;
                    $description = $month ? "{$feeCategoryName} - {$month}" : $feeCategoryName;

                    // Find student
                    $student = Student::where('nis', $nis)->first();
                    if (!$student) {
                        $errors[] = "Row {$rowNumber}: Siswa dengan NIS {$nis} tidak ditemukan";
                        $failed++;
                        continue;
                    }

                    // Find fee category by name
                    $feeCategory = FeeCategory::where('name', 'like', "%{$feeCategoryName}%")->first();
                    if (!$feeCategory) {
                        $errors[] = "Row {$rowNumber}: Fee category '{$feeCategoryName}' tidak ditemukan";
                        $failed++;
                        continue;
                    }

                    // Group by student + due date
                    $key = $student->id . '_' . $dueDate;
                    if (!isset($groupedData[$key])) {
                        $groupedData[$key] = [
                            'student_id' => $student->id,
                            'due_date' => $dueDate,
                            'items' => [],
                        ];
                    }

                    $groupedData[$key]['items'][] = [
                        'fee_category_id' => $feeCategory->id,
                        'description' => $description,
                        'amount' => $amount,
                    ];
                } catch (\Exception $e) {
                    $errors[] = "Row {$rowNumber}: " . $e->getMessage();
                    $failed++;
                }
            }

            // Create invoices
            foreach ($groupedData as $data) {
                try {
                    $invoiceNumber = $this->generateInvoiceNumber();
                    $totalAmount = array_sum(array_column($data['items'], 'amount'));

                    $invoice = Invoice::create([
                        'invoice_number' => $invoiceNumber,
                        'student_id' => $data['student_id'],
                        'academic_year_id' => $validated['academic_year_id'],
                        'total_amount' => $totalAmount,
                        'paid_amount' => 0,
                        'status' => 'UNPAID',
                        'due_date' => $data['due_date'],
                    ]);

                    foreach ($data['items'] as $item) {
                        InvoiceItem::create([
                            'invoice_id' => $invoice->id,
                            'fee_category_id' => $item['fee_category_id'],
                            'description' => $item['description'],
                            'amount' => $item['amount'],
                        ]);
                    }

                    $created++;
                } catch (\Exception $e) {
                    $errors[] = "Failed to create invoice: " . $e->getMessage();
                    $failed++;
                }
            }

            DB::commit();

            return ApiResponse::success([
                'total_rows' => count($rows),
                'invoices_created' => $created,
                'failed' => $failed,
                'errors' => $errors,
            ], "Import completed. {$created} invoices created.");
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::error('Failed to import invoices: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Generate unique invoice number
     * Format: INV/YYYY/MM/XXXX
     */
    private function generateInvoiceNumber()
    {
        $year = date('Y');
        $month = date('m');
        $prefix = "INV/{$year}/{$month}/";

        // Get last invoice number for this month
        $lastInvoice = Invoice::where('invoice_number', 'like', $prefix . '%')
            ->orderBy('invoice_number', 'desc')
            ->first();

        if ($lastInvoice) {
            $lastNumber = (int) substr($lastInvoice->invoice_number, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return $prefix . $newNumber;
    }
}
