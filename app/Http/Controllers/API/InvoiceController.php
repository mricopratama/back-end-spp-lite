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
use App\Http\Requests\GenerateMonthlySppRequest;
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
            $user = Auth::user();

            $query = Invoice::with(['student', 'academicYear', 'items.feeCategory']);

            // If user is student, only return their own invoices
            if ($user->role->name === 'student') {
                if (!$user->student_id) {
                    return ApiResponse::error('Student record not found', 404);
                }
                $query->where('student_id', $user->student_id);
            }

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by student (admin only)
            if ($request->has('student_id') && $user->role->name === 'admin') {
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

            // Get student data
            $student = Student::findOrFail($validated['student_id']);

            // Calculate total amount
            $totalAmount = 0;
            foreach ($validated['items'] as $item) {
                // Get amount based on priority:
                // 1. Custom amount (manual override) - highest priority
                // 2. Use fee_category default_amount
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
                'invoice_type' => $validated['invoice_type'],
                'period_month' => $validated['period_month'],
                'period_year' => $validated['period_year'],
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
        $user = Auth::user();

        // If user is student, only allow access to their own invoices
        if ($user->role->name === 'student') {
            if ($invoice->student_id != $user->student_id) {
                return ApiResponse::error('Forbidden: You can only access your own invoices', 403);
            }
        }

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
     * Import invoices from Excel with intelligent scanning
     * Auto-detects columns and academic year from Excel data
     */
    public function import(ImportInvoicesRequest $request)
    {
        try {
            DB::beginTransaction();

            $validated = $request->validated();
            $file = $request->file('file');
            $spreadsheet = IOFactory::load($file->getPathname());

            // Scan all sheets to find invoice data
            $dataRegion = $this->findInvoiceDataRegion($spreadsheet);

            if (!$dataRegion) {
                return ApiResponse::error(
                    'Tidak dapat menemukan data invoice di file Excel.',
                    400,
                    [
                        'help' => 'Pastikan ada kolom: NIS, Nama Siswa, Fee Category/Kategori, Jumlah, Jatuh Tempo, Bulan, Tahun Ajaran'
                    ]
                );
            }

            $worksheet = $dataRegion['worksheet'];
            $headerRow = $dataRegion['header_row'];
            $startRow = $dataRegion['start_row'];
            $endRow = $dataRegion['end_row'];
            $columnMapping = $dataRegion['columns'];

            // Determine academic year
            $academicYearId = $validated['academic_year_id'] ?? null;
            if (!$academicYearId) {
                $academicYearId = $this->detectAcademicYear($worksheet, $startRow, $endRow, $columnMapping);
            }

            if (!$academicYearId) {
                return ApiResponse::error(
                    'Tidak dapat mendeteksi tahun ajaran. Mohon sertakan academic_year_id dalam request atau tambahkan kolom Tahun Ajaran di Excel.',
                    400
                );
            }

            $created = 0;
            $failed = 0;
            $errors = [];
            $groupedData = [];

            $debugInfo = [
                'sheet_name' => $dataRegion['sheet_name'],
                'header_row' => $headerRow,
                'data_rows' => "{$startRow} to {$endRow}",
                'academic_year_id' => $academicYearId,
                'column_mapping' => $columnMapping,
            ];

            // Process data rows
            for ($row = $startRow; $row <= $endRow; $row++) {
                try {
                    // Read data
                    $nis = trim($worksheet->getCell($columnMapping['nis'] . $row)->getValue() ?? '');
                    $feeCategoryName = trim($worksheet->getCell($columnMapping['fee_category'] . $row)->getValue() ?? '');
                    $amount = trim($worksheet->getCell($columnMapping['amount'] . $row)->getValue() ?? '');
                    $dueDate = $columnMapping['due_date']
                        ? trim($worksheet->getCell($columnMapping['due_date'] . $row)->getValue() ?? '')
                        : null;
                    $month = $columnMapping['month']
                        ? trim($worksheet->getCell($columnMapping['month'] . $row)->getValue() ?? '')
                        : null;

                    // Skip empty rows
                    if (empty($nis) && empty($feeCategoryName) && empty($amount)) {
                        continue;
                    }

                    // Validate required fields
                    if (empty($nis)) {
                        $errors[] = "Row {$row}: NIS wajib diisi";
                        $failed++;
                        continue;
                    }

                    if (empty($feeCategoryName)) {
                        $errors[] = "Row {$row}: Fee Category wajib diisi";
                        $failed++;
                        continue;
                    }

                    if (empty($amount) || !is_numeric($amount)) {
                        $errors[] = "Row {$row}: Jumlah harus berupa angka";
                        $failed++;
                        continue;
                    }

                    // Parse due date
                    if (empty($dueDate)) {
                        $dueDate = now()->addDays(30)->format('Y-m-d');
                    } else {
                        // Handle Excel date serial number
                        if (is_numeric($dueDate)) {
                            $dueDate = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($dueDate)->format('Y-m-d');
                        } else {
                            try {
                                $dueDate = date('Y-m-d', strtotime($dueDate));
                            } catch (\Exception $e) {
                                $dueDate = now()->addDays(30)->format('Y-m-d');
                            }
                        }
                    }

                    // Find student
                    $student = Student::where('nis', $nis)->first();
                    if (!$student) {
                        $errors[] = "Row {$row}: Siswa dengan NIS {$nis} tidak ditemukan";
                        $failed++;
                        continue;
                    }

                    // Find fee category by name (fuzzy match)
                    $feeCategory = FeeCategory::where('name', 'like', "%{$feeCategoryName}%")->first();
                    if (!$feeCategory) {
                        $errors[] = "Row {$row}: Fee category '{$feeCategoryName}' tidak ditemukan";
                        $failed++;
                        continue;
                    }

                    $description = $month ? "{$feeCategoryName} - {$month}" : $feeCategoryName;

                    // Group by student + due date
                    $key = $student->id . '_' . $dueDate;
                    if (!isset($groupedData[$key])) {
                        $groupedData[$key] = [
                            'student_id' => $student->id,
                            'student_nis' => $nis,
                            'due_date' => $dueDate,
                            'items' => [],
                        ];
                    }

                    $groupedData[$key]['items'][] = [
                        'fee_category_id' => $feeCategory->id,
                        'description' => $description,
                        'amount' => (float) $amount,
                    ];
                } catch (\Exception $e) {
                    $errors[] = "Row {$row}: " . $e->getMessage();
                    $failed++;
                }
            }

            // Create invoices
            foreach ($groupedData as $data) {
                try {
                    $invoiceNumber = $this->generateInvoiceNumber();
                    $totalAmount = array_sum(array_column($data['items'], 'amount'));

                    // Generate title from items
                    $itemNames = array_map(function($item) {
                        return $item['description'];
                    }, $data['items']);
                    $title = count($itemNames) > 1
                        ? implode(', ', array_slice($itemNames, 0, 2)) . (count($itemNames) > 2 ? '...' : '')
                        : ($itemNames[0] ?? 'Invoice Tagihan');

                    $invoice = Invoice::create([
                        'invoice_number' => $invoiceNumber,
                        'title' => $title,
                        'student_id' => $data['student_id'],
                        'academic_year_id' => $academicYearId,
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
                    $errors[] = "Failed to create invoice for NIS {$data['student_nis']}: " . $e->getMessage();
                    $failed++;
                }
            }

            DB::commit();

            return ApiResponse::success([
                'invoices_created' => $created,
                'failed' => $failed,
                'errors' => array_slice($errors, 0, 20), // Limit to first 20 errors
                'import_info' => $debugInfo,
            ], "Import completed. {$created} invoices created, {$failed} failed.");
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::error('Failed to import invoices: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Find invoice data region across all sheets
     */
    private function findInvoiceDataRegion($spreadsheet)
    {
        for ($sheetIndex = 0; $sheetIndex < $spreadsheet->getSheetCount(); $sheetIndex++) {
            $worksheet = $spreadsheet->getSheet($sheetIndex);
            $sheetName = $worksheet->getTitle();

            $region = $this->detectInvoiceDataRegionInSheet($worksheet, $sheetName);
            if ($region) {
                return $region;
            }
        }

        return null;
    }

    /**
     * Detect invoice data region in a sheet
     */
    private function detectInvoiceDataRegionInSheet($worksheet, $sheetName)
    {
        $highestRow = $worksheet->getHighestRow();
        $highestColumn = $worksheet->getHighestColumn();
        $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

        $searchMaxRow = min($highestRow, 100);
        $searchMaxCol = min($highestColumnIndex, 20);

        for ($row = 1; $row <= $searchMaxRow; $row++) {
            $columnMapping = $this->detectInvoiceColumnsInRow($worksheet, $row, $searchMaxCol);

            if ($columnMapping['nis'] && $columnMapping['fee_category'] && $columnMapping['amount']) {
                $dataStartRow = $row + 1;
                $dataEndRow = $this->findInvoiceDataEndRow($worksheet, $dataStartRow, $highestRow, $columnMapping);

                if ($dataEndRow >= $dataStartRow) {
                    return [
                        'worksheet' => $worksheet,
                        'sheet_name' => $sheetName,
                        'header_row' => $row,
                        'start_row' => $dataStartRow,
                        'end_row' => $dataEndRow,
                        'columns' => $columnMapping,
                    ];
                }
            }
        }

        return null;
    }

    /**
     * Detect invoice columns in a row
     */
    private function detectInvoiceColumnsInRow($worksheet, $row, $maxCol)
    {
        $mapping = [
            'nis' => null,
            'name' => null,
            'class' => null,
            'fee_category' => null,
            'amount' => null,
            'due_date' => null,
            'month' => null,
            'academic_year' => null,
        ];

        for ($col = 1; $col <= $maxCol; $col++) {
            $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
            $cell = $worksheet->getCell($columnLetter . $row);
            $headerValue = $cell->getValue();

            if ($headerValue instanceof \PhpOffice\PhpSpreadsheet\RichText\RichText) {
                $headerValue = $headerValue->getPlainText();
            }

            $normalized = preg_replace('/\s+/', ' ', strtolower(trim($headerValue ?? '')));
            $normalized = preg_replace('/[^a-z0-9\s]/', '', $normalized);

            if (!$mapping['nis'] && preg_match('/\bnis\b/', $normalized)) {
                $mapping['nis'] = $columnLetter;
            }
            if (!$mapping['name'] && preg_match('/\bnama\s*siswa\b|\bname\b|\bnama\b/', $normalized)) {
                $mapping['name'] = $columnLetter;
            }
            if (!$mapping['class'] && preg_match('/\bkelas\b|\bclass\b/', $normalized)) {
                $mapping['class'] = $columnLetter;
            }
            if (!$mapping['fee_category'] && preg_match('/\bfee\s*category\b|\bkategori\b|\bcategory\b/', $normalized)) {
                $mapping['fee_category'] = $columnLetter;
            }
            if (!$mapping['amount'] && preg_match('/\bjumlah\b|\bamount\b|\btotal\b|\bnominal\b/', $normalized)) {
                $mapping['amount'] = $columnLetter;
            }
            if (!$mapping['due_date'] && preg_match('/\bjatuh\s*tempo\b|\bdue\s*date\b|\btempo\b/', $normalized)) {
                $mapping['due_date'] = $columnLetter;
            }
            if (!$mapping['month'] && preg_match('/\bbulan\b|\bmonth\b/', $normalized)) {
                $mapping['month'] = $columnLetter;
            }
            if (!$mapping['academic_year'] && preg_match('/\btahun\s*ajaran\b|\bacademic\s*year\b/', $normalized)) {
                $mapping['academic_year'] = $columnLetter;
            }
        }

        return $mapping;
    }

    /**
     * Find last row with invoice data
     */
    private function findInvoiceDataEndRow($worksheet, $startRow, $maxRow, $columnMapping)
    {
        $emptyRowCount = 0;
        $lastDataRow = $startRow - 1;

        for ($row = $startRow; $row <= $maxRow; $row++) {
            $nis = trim($worksheet->getCell($columnMapping['nis'] . $row)->getValue() ?? '');
            $amount = trim($worksheet->getCell($columnMapping['amount'] . $row)->getValue() ?? '');

            if (!empty($nis) || !empty($amount)) {
                $lastDataRow = $row;
                $emptyRowCount = 0;
            } else {
                $emptyRowCount++;
                if ($emptyRowCount >= 5) {
                    break;
                }
            }
        }

        return $lastDataRow;
    }

    /**
     * Auto-detect academic year from Excel data or use active year
     */
    private function detectAcademicYear($worksheet, $startRow, $endRow, $columnMapping)
    {
        // If there's an academic year column, read from first data row
        if ($columnMapping['academic_year']) {
            $yearText = trim($worksheet->getCell($columnMapping['academic_year'] . $startRow)->getValue() ?? '');

            if (!empty($yearText)) {
                // Try to find matching academic year by name
                $academicYear = \App\Models\AcademicYear::where('name', 'like', "%{$yearText}%")->first();
                if ($academicYear) {
                    return $academicYear->id;
                }
            }
        }

        // Fallback to active academic year
        $activeYear = \App\Models\AcademicYear::where('is_active', true)->first();
        return $activeYear ? $activeYear->id : null;
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

    // ========================================
    // SECTION: MONTHLY SPP GENERATION
    // ========================================

    /**
     * Generate SPP invoices for a specific month
     */
    public function generateMonthlySpp(GenerateMonthlySppRequest $request)
    {
        try {
            DB::beginTransaction();

            $validated = $request->validated();

            // Get student IDs
            $studentIds = $this->getStudentIds(
                $validated['academic_year_id'],
                $validated['class_id'] ?? null,
                $validated['student_ids'] ?? null
            );

            if (empty($studentIds)) {
                return ApiResponse::error('No students found', 400);
            }

            // Get SPP fee category
            $sppCategory = FeeCategory::where('name', 'LIKE', '%SPP%')
                ->orWhere('name', 'LIKE', '%spp%')
                ->first();

            if (!$sppCategory) {
                return ApiResponse::error('SPP fee category not found. Please create "SPP Bulanan" fee category first.', 400);
            }

            $created = 0;
            $skipped = 0;
            $details = [];

            foreach ($studentIds as $studentId) {
                $student = Student::find($studentId);

                // Check if invoice already exists for this period
                $exists = Invoice::where([
                    'student_id' => $studentId,
                    'academic_year_id' => $validated['academic_year_id'],
                    'period_month' => $validated['period_month'],
                    'period_year' => $validated['period_year'],
                    'invoice_type' => 'spp_monthly'
                ])->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                // Use fee category default amount
                $amount = $sppCategory->default_amount;

                // Generate invoice
                $monthName = $this->getIndonesianMonthName($validated['period_month']);

                $invoice = Invoice::create([
                    'invoice_number' => $this->generateInvoiceNumber(),
                    'title' => "SPP Bulan {$monthName} {$validated['period_year']}",
                    'period_month' => $validated['period_month'],
                    'period_year' => $validated['period_year'],
                    'invoice_type' => 'spp_monthly',
                    'student_id' => $studentId,
                    'academic_year_id' => $validated['academic_year_id'],
                    'total_amount' => $amount,
                    'paid_amount' => 0,
                    'status' => 'unpaid',
                    'due_date' => $validated['due_date'],
                ]);

                // Create invoice item
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'fee_category_id' => $sppCategory->id,
                    'description' => "SPP Bulan {$monthName} {$validated['period_year']}",
                    'amount' => $amount,
                ]);

                $created++;
                $details[] = [
                    'student_id' => $studentId,
                    'student_name' => $student->full_name,
                    'student_nis' => $student->nis,
                    'invoice_number' => $invoice->invoice_number,
                    'amount' => $amount,
                    'period' => "{$monthName} {$validated['period_year']}",
                    'due_date' => $validated['due_date'],
                ];
            }

            DB::commit();

            return ApiResponse::success([
                'created' => $created,
                'skipped' => $skipped,
                'total_students' => count($studentIds),
                'total_amount' => array_sum(array_column($details, 'amount')),
                'period' => $this->getIndonesianMonthName($validated['period_month']) . ' ' . $validated['period_year'],
                'details' => $details,
            ], "Successfully generated {$created} monthly SPP invoices");

        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::error('Failed to generate monthly SPP: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get monthly payment status for a student
     */
    public function getMonthlyPaymentStatus(Request $request, $studentId)
    {
        try {
            $validated = $request->validate([
                'academic_year_id' => 'required|exists:academic_years,id',
            ]);

            $student = Student::findOrFail($studentId);
            $academicYear = \App\Models\AcademicYear::findOrFail($validated['academic_year_id']);

            // Get all monthly SPP invoices for this student in this academic year
            $invoices = Invoice::where([
                'student_id' => $studentId,
                'academic_year_id' => $validated['academic_year_id'],
                'invoice_type' => 'spp_monthly'
            ])->get();

            // Determine academic year range (Juli - Juni)
            $startYear = (int) substr($academicYear->name, 0, 4);
            $endYear = $startYear + 1;

            // Build month array (Juli - Juni for school year)
            $months = [];
            for ($i = 7; $i <= 12; $i++) {
                $months[] = $this->buildMonthData($student, $invoices, $i, $startYear, $validated['academic_year_id']);
            }
            for ($i = 1; $i <= 6; $i++) {
                $months[] = $this->buildMonthData($student, $invoices, $i, $endYear, $validated['academic_year_id']);
            }

            // Calculate summary
            $summary = $this->calculatePaymentSummary($months);

            return ApiResponse::success([
                'student' => [
                    'id' => $student->id,
                    'nis' => $student->nis,
                    'full_name' => $student->full_name,
                ],
                'academic_year' => [
                    'id' => $academicYear->id,
                    'name' => $academicYear->name,
                ],
                'months' => $months,
                'summary' => $summary,
            ], 'Monthly payment status fetched successfully');

        } catch (\Exception $e) {
            return ApiResponse::error('Failed to fetch payment status: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Generate missing months for a student
     */
    public function generateMissingMonths(Request $request)
    {
        try {
            DB::beginTransaction();

            $validated = $request->validate([
                'academic_year_id' => 'required|exists:academic_years,id',
                'student_id' => 'required|exists:students,id',
                'months' => 'required|array',
                'months.*.month' => 'required|integer|min:1|max:12',
                'months.*.year' => 'required|integer|min:2024|max:2030',
                'months.*.due_date' => 'required|date',
            ]);

            $student = Student::findOrFail($validated['student_id']);
            $sppCategory = FeeCategory::where('name', 'LIKE', '%SPP%')->first();

            if (!$sppCategory) {
                return ApiResponse::error('SPP fee category not found', 400);
            }

            $created = 0;
            $details = [];

            foreach ($validated['months'] as $monthData) {
                // Check if already exists
                $exists = Invoice::where([
                    'student_id' => $validated['student_id'],
                    'academic_year_id' => $validated['academic_year_id'],
                    'period_month' => $monthData['month'],
                    'period_year' => $monthData['year'],
                    'invoice_type' => 'spp_monthly'
                ])->exists();

                if ($exists) {
                    continue;
                }

                $amount = $sppCategory->default_amount;
                $monthName = $this->getIndonesianMonthName($monthData['month']);

                $invoice = Invoice::create([
                    'invoice_number' => $this->generateInvoiceNumber(),
                    'title' => "SPP Bulan {$monthName} {$monthData['year']}",
                    'period_month' => $monthData['month'],
                    'period_year' => $monthData['year'],
                    'invoice_type' => 'spp_monthly',
                    'student_id' => $validated['student_id'],
                    'academic_year_id' => $validated['academic_year_id'],
                    'total_amount' => $amount,
                    'paid_amount' => 0,
                    'status' => 'unpaid',
                    'due_date' => $monthData['due_date'],
                ]);

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'fee_category_id' => $sppCategory->id,
                    'description' => "SPP Bulan {$monthName} {$monthData['year']}",
                    'amount' => $amount,
                ]);

                $created++;
                $details[] = [
                    'invoice_number' => $invoice->invoice_number,
                    'period' => "{$monthName} {$monthData['year']}",
                    'amount' => $amount,
                ];
            }

            DB::commit();

            return ApiResponse::success([
                'created' => $created,
                'details' => $details,
            ], "Successfully generated {$created} missing invoices");

        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::error('Failed to generate missing months: ' . $e->getMessage(), 500);
        }
    }

    // ========================================
    // PRIVATE HELPER METHODS
    // ========================================

    /**
     * Get student IDs based on class or specific students
     */
    private function getStudentIds($academicYearId, $classId = null, $studentIds = null)
    {
        if ($studentIds) {
            return $studentIds;
        }

        if ($classId) {
            return StudentClassHistory::where([
                'class_id' => $classId,
                'academic_year_id' => $academicYearId,
            ])->pluck('student_id')->toArray();
        }

        return [];
    }

    /**
     * Get Indonesian month name
     */
    private function getIndonesianMonthName($month)
    {
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
            4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September',
            10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        return $months[$month] ?? '';
    }

    /**
     * Build month data for payment status
     */
    private function buildMonthData($student, $invoices, $month, $year, $academicYearId)
    {
        $invoice = $invoices->where('period_month', $month)
                            ->where('period_year', $year)
                            ->first();

        $monthName = $this->getIndonesianMonthName($month);

        if (!$invoice) {
            return [
                'month' => $month,
                'month_name' => $monthName,
                'year' => $year,
                'period' => "{$monthName} {$year}",
                'invoice_id' => null,
                'status' => 'not_generated',
                'message' => 'Invoice belum dibuat',
            ];
        }

        $data = [
            'month' => $month,
            'month_name' => $monthName,
            'year' => $year,
            'period' => "{$monthName} {$year}",
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'total_amount' => $invoice->total_amount,
            'paid_amount' => $invoice->paid_amount,
            'remaining_amount' => $invoice->total_amount - $invoice->paid_amount,
            'status' => $invoice->status,
            'due_date' => $invoice->due_date->format('Y-m-d'),
        ];

        // Add overdue info if unpaid/partial
        if ($invoice->isOverdue()) {
            $data['overdue'] = true;
            $data['overdue_days'] = $invoice->overdue_days;
        }

        // Add payment date if paid
        if ($invoice->status === 'paid') {
            $lastPayment = $invoice->payments()->latest('payment_date')->first();
            if ($lastPayment) {
                $data['payment_date'] = $lastPayment->payment_date->format('Y-m-d');
            }
        }

        return $data;
    }

    /**
     * Calculate payment summary
     */
    private function calculatePaymentSummary($months)
    {
        $summary = [
            'total_months' => count($months),
            'paid' => 0,
            'partial' => 0,
            'unpaid' => 0,
            'not_generated' => 0,
            'total_amount' => 0,
            'total_paid' => 0,
            'total_unpaid' => 0,
            'total_outstanding' => 0,
        ];

        foreach ($months as $month) {
            if ($month['status'] === 'paid') {
                $summary['paid']++;
                $summary['total_amount'] += $month['total_amount'];
                $summary['total_paid'] += $month['paid_amount'];
            } elseif ($month['status'] === 'partial') {
                $summary['partial']++;
                $summary['total_amount'] += $month['total_amount'];
                $summary['total_paid'] += $month['paid_amount'];
                $summary['total_outstanding'] += $month['remaining_amount'];
            } elseif ($month['status'] === 'unpaid') {
                $summary['unpaid']++;
                $summary['total_amount'] += $month['total_amount'];
                $summary['total_outstanding'] += $month['total_amount'];
            } else {
                $summary['not_generated']++;
            }
        }

        $summary['total_unpaid'] = $summary['total_amount'] - $summary['total_paid'];

        return $summary;
    }
}
