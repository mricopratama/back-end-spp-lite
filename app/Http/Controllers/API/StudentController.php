<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\User;
use App\Models\Classes;
use App\Models\StudentClassHistory;
use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Http\Requests\SetStudentClassRequest;
use App\Http\Requests\BulkPromoteRequest;
use App\Http\Requests\ImportStudentsRequest;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class StudentController extends Controller
{
    /**
     * Display a listing of students with advanced filters and search
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();

            $query = Student::query();

            // If user is student, only return their own data
            if ($user->role->name === 'student') {
                if (!$user->student_id) {
                    return ApiResponse::error('Student record not found', 404);
                }
                $query->where('id', $user->student_id);
            }

            // Optimize: Only load relationships if needed
            $with = ['currentClass'];
            if ($request->get('with_user', false)) {
                $with[] = 'user';
            }
            $query->with($with);

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by class
            if ($request->has('class_id')) {
                $query->whereHas('classHistory', function ($q) use ($request) {
                    $q->where('class_id', $request->class_id);
                });
            }

            // Filter by academic year
            if ($request->has('academic_year_id')) {
                $query->whereHas('classHistory', function ($q) use ($request) {
                    $q->where('academic_year_id', $request->academic_year_id);
                });
            }

            // Enhanced search: name, NIS, address, phone
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('full_name', 'like', "%{$search}%")
                      ->orWhere('nis', 'like', "%{$search}%")
                      ->orWhere('address', 'like', "%{$search}%")
                      ->orWhere('phone_number', 'like', "%{$search}%");
                });
            }

            // Filter by NIS only (exact match)
            if ($request->has('nis')) {
                $query->where('nis', $request->nis);
            }

            // Filter by name only
            if ($request->has('name')) {
                $query->where('full_name', 'like', "%{$request->name}%");
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'full_name');
            $sortOrder = $request->get('sort_order', 'asc');

            // Validate sort column
            $allowedSorts = ['full_name', 'nis', 'status', 'created_at'];
            if (in_array($sortBy, $allowedSorts)) {
                $query->orderBy($sortBy, $sortOrder);
            } else {
                $query->orderBy('full_name', 'asc');
            }

            // Check if pagination is requested
            if ($request->get('paginate', true) === 'false' || $request->get('paginate') === false) {
                $students = $query->get();
                return ApiResponse::success($students, 'List of students');
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $students = $query->paginate($perPage);

            return ApiResponse::success($students, 'List of students');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to fetch students: ' . $e->getMessage(), 500);
        }
    }

        /**
     * Search students by name or NIS with academic year validation
     * Endpoint: /api/students/search
     * Query params:
     *   - search: string (name or NIS, required)
     *   - academic_year_id: int (optional, default: active year)
     *   - per_page: int (optional, default: 15)
     */
    public function search(Request $request)
    {
        $request->validate([
            'search' => 'required|string|max:255',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'year' => ['nullable', 'string', 'regex:/^\d{4}(-|\/)\d{4}$|^\d{4}$/'],
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $search = $request->search;
        $academicYearId = $request->academic_year_id;
        $yearParam = $request->year;

        $query = Student::query();

        // Filter academic year
        if ($academicYearId) {
            $query->whereHas('classHistory', function ($q) use ($academicYearId) {
                $q->where('academic_year_id', $academicYearId);
            });
        } elseif ($yearParam) {
            if (strpos($yearParam, '-') !== false) {
                // Format: 2024-2025 -> 2024/2025
                $academicYearPattern = str_replace('-', '/', $yearParam);
                $query->whereHas('classHistory.academicYear', function ($q) use ($academicYearPattern) {
                    $q->where('name', $academicYearPattern);
                });
            } elseif (strpos($yearParam, '/') !== false) {
                // Format: 2024/2025 (exact match)
                $academicYearPattern = $yearParam;
                $query->whereHas('classHistory.academicYear', function ($q) use ($academicYearPattern) {
                    $q->where('name', $academicYearPattern);
                });
            } else {
                // Format: 2024 -> 2024/%
                $academicYearPattern = $yearParam . '/%';
                $query->whereHas('classHistory.academicYear', function ($q) use ($academicYearPattern) {
                    $q->where('name', 'like', $academicYearPattern);
                });
            }
        } else {
            // Default: tahun ajar aktif
            $activeYear = \App\Models\AcademicYear::where('is_active', true)->first();
            if (!$activeYear) {
                return ApiResponse::error('Tidak ada tahun ajar aktif', 422);
            }
            $academicYearId = $activeYear->id;
            $query->whereHas('classHistory', function ($q) use ($academicYearId) {
                $q->where('academic_year_id', $academicYearId);
            });
        }

        $query->where(function ($q) use ($search) {
            $q->where('full_name', 'like', "%{$search}%")
              ->orWhere('nis', 'like', "%{$search}%");
        });

        $perPage = $request->get('per_page', 15);
        $students = $query->paginate($perPage);

        return ApiResponse::success($students, 'Search result');
    }

    /**
     * Get paginated students list with customizable parameters
     *
     * Query Parameters:
     * - page: Page number (default: 1)
     * - per_page: Items per page (default: 15, max: 100)
     * - academic_year_id: Filter by academic year ID (exact match)
     * - year: Filter by year (supports: 2024, 2025, or 2024-2025)
     * - class_id: Filter by class
     * - status: Filter by status (active, inactive, graduated, dropped)
     * - search: Search by name, NIS, address, or phone
     * - sort_by: Sort column (full_name, nis, status, created_at)
     * - sort_order: Sort direction (asc, desc)
     */
    public function paginate(Request $request)
    {
        try {
            $user = Auth::user();

            // Validate pagination parameters
            $request->validate([
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100',
                'academic_year_id' => 'nullable|exists:academic_years,id',
                // year: 2024, 2024-2025, atau 2024/2025
                'year' => ['nullable', 'string', 'regex:/^\d{4}(-|\/)\d{4}$|^\d{4}$/'],
                'class_id' => 'nullable|exists:classes,id',
                'status' => 'nullable|in:active,inactive,graduated,dropped',
                'search' => 'nullable|string|max:255',
                'sort_by' => 'nullable|in:full_name,nis,status,created_at',
                'sort_order' => 'nullable|in:asc,desc',
            ]);

            $query = Student::query();

            // If user is student, only return their own data
            if ($user->role->name === 'student') {
                if (!$user->student_id) {
                    return ApiResponse::error('Student record not found', 404);
                }
                $query->where('id', $user->student_id);
            }

            // Load relationships
            $query->with(['currentClassHistory.class', 'currentClassHistory.academicYear']);

            // Filter by academic year (supports both ID and year)
            if ($request->has('academic_year_id')) {
                // Filter by exact academic year ID
                $query->whereHas('classHistory', function ($q) use ($request) {
                    $q->where('academic_year_id', $request->academic_year_id);
                });
            } elseif ($request->has('year')) {
                // Filter by year (supports multiple formats)
                $yearParam = $request->year;

                // Ubah format 2024-2025 atau 2024/2025 menjadi 2024/2025 (exact match),
                // dan 2024 menjadi 2024/%
                if (strpos($yearParam, '-') !== false) {
                    // Format: 2024-2025 -> 2024/2025
                    $academicYearPattern = str_replace('-', '/', $yearParam);
                    $query->whereHas('classHistory.academicYear', function ($q) use ($academicYearPattern) {
                        $q->where('name', $academicYearPattern);
                    });
                } elseif (strpos($yearParam, '/') !== false) {
                    // Format: 2024/2025 (exact match)
                    $academicYearPattern = $yearParam;
                    $query->whereHas('classHistory.academicYear', function ($q) use ($academicYearPattern) {
                        $q->where('name', $academicYearPattern);
                    });
                } else {
                    // Format: 2024 -> 2024/%
                    $academicYearPattern = $yearParam . '/%';
                    $query->whereHas('classHistory.academicYear', function ($q) use ($academicYearPattern) {
                        $q->where('name', 'like', $academicYearPattern);
                    });
                }
            }

            // Filter by class
            if ($request->has('class_id')) {
                $query->whereHas('classHistory', function ($q) use ($request) {
                    $q->where('class_id', $request->class_id);
                });
            }

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Search functionality
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('full_name', 'like', "%{$search}%")
                      ->orWhere('nis', 'like', "%{$search}%")
                      ->orWhere('address', 'like', "%{$search}%")
                      ->orWhere('phone_number', 'like', "%{$search}%");
                });
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'full_name');
            $sortOrder = $request->get('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $perPage = min($perPage, 100); // Max 100 items per page

            $students = $query->paginate($perPage);

            // Transform data to include current class info more clearly

            $transformedData = $students->getCollection()->map(function ($student) {
                return [
                    'id' => $student->id,
                    'nis' => $student->nis,
                    'full_name' => $student->full_name,
                    'address' => $student->address,
                    'phone_number' => $student->phone_number,
                    'status' => $student->status,
                    'current_class' => $student->current_class_info,
                    'created_at' => $student->created_at,
                    'updated_at' => $student->updated_at,
                ];
            });

            $students->setCollection($transformedData);

            // Add metadata
            $metadata = [
                'current_page' => $students->currentPage(),
                'per_page' => $students->perPage(),
                'total' => $students->total(),
                'last_page' => $students->lastPage(),
                'from' => $students->firstItem(),
                'to' => $students->lastItem(),
                'filters' => [
                    'academic_year_id' => $request->academic_year_id,
                    'year' => $request->year,
                    'class_id' => $request->class_id,
                    'status' => $request->status,
                    'search' => $request->search,
                ],
            ];

            return ApiResponse::success([
                'data' => $students->items(),
                'pagination' => $metadata,
            ], 'Students retrieved successfully');

        } catch (\Exception $e) {
            return ApiResponse::error('Failed to fetch students: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Store a newly created student
     */
    public function store(StoreStudentRequest $request)
    {
        try {
            DB::beginTransaction();

            $student = Student::create($request->validated());

            DB::commit();

            return ApiResponse::success(
                $student->load('currentClass'),
                'Student created successfully',
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::error('Failed to create student: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified student with history
     */
    public function show($id)
    {
        try {
            $user = Auth::user();

            // If user is student, only allow access to their own data
            if ($user->role->name === 'student') {
                if ($user->student_id != $id) {
                    return ApiResponse::error('Forbidden: You can only access your own data', 403);
                }
            }

            $student = Student::with([
                'classHistory.academicYear',
                'classHistory.class',
                'user'
            ])->findOrFail($id);

            return ApiResponse::success($student, 'Student detail fetched');
        } catch (\Exception $e) {
            return ApiResponse::error('Student not found', 404);
        }
    }

    /**
     * Update the specified student
     */
    public function update(UpdateStudentRequest $request, $id)
    {
        try {
            DB::beginTransaction();

            $student = Student::findOrFail($id);
            $student->update($request->validated());

            DB::commit();

            return ApiResponse::success(
                $student->load('currentClass'),
                'Student updated successfully'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::error('Failed to update student: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified student
     */
    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $student = Student::findOrFail($id);

            // Check if student has invoices
            if ($student->invoices()->exists()) {
                return ApiResponse::error(
                    'Cannot delete student with existing invoices. Set status to INACTIVE instead.',
                    400
                );
            }

            // Delete associated user if exists
            if ($student->user) {
                $student->user->delete();
            }

            $student->delete();

            DB::commit();

            return ApiResponse::success(null, 'Student deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::error('Failed to delete student: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Set student class for academic year (Promote/Assign)
     */
    public function setClass(SetStudentClassRequest $request)
    {
        try {
            DB::beginTransaction();

            $validated = $request->validated();

            // Check if already assigned
            $exists = StudentClassHistory::where([
                'student_id' => $validated['student_id'],
                'academic_year_id' => $validated['academic_year_id'],
            ])->exists();

            if ($exists) {
                return ApiResponse::error(
                    'Student already assigned to a class for this academic year',
                    400
                );
            }

            $history = StudentClassHistory::create($validated);

            DB::commit();

            $student = Student::with('currentClass')->find($validated['student_id']);
            $class = Classes::find($validated['class_id']);

            return ApiResponse::success([
                'history_id' => $history->id,
                'student_name' => $student->full_name,
                'class_name' => $class->name,
                'academic_year' => $history->academicYear->name,
            ], 'Student assigned to class successfully', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::error('Failed to assign class: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Bulk promote students to next academic year
     */
    public function bulkPromote(BulkPromoteRequest $request)
    {
        try {
            DB::beginTransaction();

            $validated = $request->validated();
            $promoted = 0;
            $skipped = 0;

            foreach ($validated['class_mapping'] as $mapping) {
                // Get students from source class
                $students = StudentClassHistory::where([
                    'academic_year_id' => $validated['from_academic_year_id'],
                    'class_id' => $mapping['from_class_id'],
                ])->with('student')->get();

                foreach ($students as $history) {
                    // Skip if already promoted
                    $exists = StudentClassHistory::where([
                        'student_id' => $history->student_id,
                        'academic_year_id' => $validated['to_academic_year_id'],
                    ])->exists();

                    if ($exists) {
                        $skipped++;
                        continue;
                    }

                    // Create new history for next year
                    StudentClassHistory::create([
                        'student_id' => $history->student_id,
                        'class_id' => $mapping['to_class_id'],
                        'academic_year_id' => $validated['to_academic_year_id'],
                    ]);

                    $promoted++;
                }
            }

            DB::commit();

            return ApiResponse::success([
                'promoted_count' => $promoted,
                'skipped_count' => $skipped,
                'total_processed' => $promoted + $skipped,
            ], 'Bulk promotion completed successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::error('Failed to promote students: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Generate user account for student
     * Username & Password: nama depan 2 kata + kelas (alikaazalea11)
     */
    public function createUserAccount($id)
    {
        try {
            DB::beginTransaction();

            $student = Student::with('currentClass')->findOrFail($id);

            // Check if user already exists
            if ($student->user) {
                return ApiResponse::error('User account already exists for this student', 400);
            }

            // Generate username from name + class
            $username = $this->generateUsername($student->full_name, $student->currentClass?->name);

            // Check if username exists, add number suffix if needed
            $originalUsername = $username;
            $counter = 1;
            while (User::where('username', $username)->exists()) {
                $username = $originalUsername . $counter;
                $counter++;
            }

            // Create user
            $user = User::create([
                'username' => $username,
                'password_hash' => Hash::make($username), // Password sama dengan username
                'full_name' => $student->full_name,
                'role_id' => 2, // Assuming role_id 2 is for students
                'student_id' => $student->id,
            ]);

            DB::commit();

            return ApiResponse::success([
                'user_id' => $user->id,
                'username' => $username,
                'default_password' => $username,
                'role' => 'student',
            ], 'User account created successfully', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::error('Failed to create user account: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Import students from Excel with intelligent scanning
     * Scans all sheets and finds data automatically
     */
    public function import(ImportStudentsRequest $request)
    {
        try {
            DB::beginTransaction();

            $file = $request->file('file');
            $spreadsheet = IOFactory::load($file->getPathname());

            // Scan all sheets to find student data
            $dataRegion = $this->findStudentDataRegion($spreadsheet);

            if (!$dataRegion) {
                return ApiResponse::error(
                    'Tidak dapat menemukan data siswa di file Excel. ' .
                    'Pastikan ada kolom dengan header NIS dan Nama di salah satu sheet.',
                    400,
                    [
                        'total_sheets' => $spreadsheet->getSheetCount(),
                        'sheets_scanned' => array_map(function($i) use ($spreadsheet) {
                            return $spreadsheet->getSheet($i)->getTitle();
                        }, range(0, $spreadsheet->getSheetCount() - 1)),
                        'help' => 'Sistem akan otomatis mencari data di semua sheet. Pastikan ada header yang jelas seperti: NIS, Nama, Alamat, No HP, Status'
                    ]
                );
            }

            $worksheet = $dataRegion['worksheet'];
            $headerRow = $dataRegion['header_row'];
            $startRow = $dataRegion['start_row'];
            $endRow = $dataRegion['end_row'];
            $columnMapping = $dataRegion['columns'];

            $inserted = 0;
            $failed = 0;
            $errors = [];
            $debugInfo = [
                'sheet_name' => $dataRegion['sheet_name'],
                'header_row' => $headerRow,
                'data_start_row' => $startRow,
                'data_end_row' => $endRow,
                'column_mapping' => [
                    'nis' => $columnMapping['nis'],
                    'name' => $columnMapping['name'],
                    'address' => $columnMapping['address'] ?: 'Not found',
                    'phone' => $columnMapping['phone'] ?: 'Not found',
                    'status' => $columnMapping['status'] ?: 'Not found',
                ]
            ];

            // Process data rows
            for ($row = $startRow; $row <= $endRow; $row++) {
                try {
                    // Read data using detected column positions
                    $nis = trim($worksheet->getCell($columnMapping['nis'] . $row)->getValue() ?? '');
                    $fullName = trim($worksheet->getCell($columnMapping['name'] . $row)->getValue() ?? '');
                    $address = $columnMapping['address']
                        ? trim($worksheet->getCell($columnMapping['address'] . $row)->getValue() ?? '')
                        : '';
                    $phoneNumber = $columnMapping['phone']
                        ? trim($worksheet->getCell($columnMapping['phone'] . $row)->getValue() ?? '')
                        : '';
                    $status = $columnMapping['status']
                        ? trim($worksheet->getCell($columnMapping['status'] . $row)->getValue() ?? 'ACTIVE')
                        : 'ACTIVE';

                    // Skip completely empty rows
                    if (empty($nis) && empty($fullName) && empty($address) && empty($phoneNumber)) {
                        continue;
                    }

                    // Debug info for first few data rows
                    if (count($debugInfo['sample_data'] ?? []) < 3) {
                        $debugInfo['sample_data'][] = "Row {$row}: NIS='{$nis}', Name='{$fullName}'";
                    }

                    // Validate required fields
                    if (empty($nis)) {
                        $errors[] = "Row {$row}: NIS wajib diisi";
                        $failed++;
                        continue;
                    }

                    if (empty($fullName)) {
                        $errors[] = "Row {$row}: Nama wajib diisi";
                        $failed++;
                        continue;
                    }

                    // Check if NIS already exists
                    if (Student::where('nis', $nis)->exists()) {
                        $errors[] = "Row {$row}: NIS {$nis} sudah terdaftar";
                        $failed++;
                        continue;
                    }

                    // Normalize status
                    $status = strtoupper($status);
                    if (!in_array($status, ['ACTIVE', 'GRADUATED', 'DROPPED_OUT', 'TRANSFERRED'])) {
                        $status = 'ACTIVE';
                    }

                    // Create student
                    Student::create([
                        'nis' => $nis,
                        'full_name' => $fullName,
                        'address' => $address ?: null,
                        'phone_number' => $phoneNumber ?: null,
                        'status' => $status,
                    ]);

                    $inserted++;
                } catch (\Exception $e) {
                    $errors[] = "Row {$row}: " . $e->getMessage();
                    $failed++;
                }
            }

            DB::commit();

            $response = [
                'total_rows' => $endRow - $startRow + 1,
                'inserted' => $inserted,
                'failed' => $failed,
                'errors' => array_slice($errors, 0, 20), // Limit errors to first 20
            ];

            // Add debug info
            $response['import_info'] = $debugInfo;

            return ApiResponse::success(
                $response,
                "Import completed. {$inserted} students inserted, {$failed} failed."
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::error('Failed to import students: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Find student data region across all sheets
     * Returns the best matching data region
     */
    private function findStudentDataRegion($spreadsheet)
    {
        $bestMatch = null;
        $bestScore = 0;

        // Scan all sheets
        for ($sheetIndex = 0; $sheetIndex < $spreadsheet->getSheetCount(); $sheetIndex++) {
            $worksheet = $spreadsheet->getSheet($sheetIndex);
            $sheetName = $worksheet->getTitle();

            // Find data region in this sheet
            $region = $this->detectDataRegionInSheet($worksheet, $sheetName);

            if ($region && $region['score'] > $bestScore) {
                $bestScore = $region['score'];
                $bestMatch = $region;
            }
        }

        return $bestMatch;
    }

    /**
     * Detect data region in a single sheet
     */
    private function detectDataRegionInSheet($worksheet, $sheetName)
    {
        $highestRow = $worksheet->getHighestRow();
        $highestColumn = $worksheet->getHighestColumn();
        $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

        // Limit search area to prevent timeout (max 100 rows, 20 columns)
        $searchMaxRow = min($highestRow, 100);
        $searchMaxCol = min($highestColumnIndex, 20);

        // Find header row by scanning for NIS and Nama columns
        for ($row = 1; $row <= $searchMaxRow; $row++) {
            $columnMapping = $this->detectStudentColumnsInRow($worksheet, $row, $searchMaxCol);

            if ($columnMapping['nis'] && $columnMapping['name']) {
                // Found potential header row
                // Now find where data ends
                $dataStartRow = $row + 1;
                $dataEndRow = $this->findDataEndRow($worksheet, $dataStartRow, $highestRow, $columnMapping);

                if ($dataEndRow >= $dataStartRow) {
                    // Calculate score based on data quality
                    $score = $this->calculateDataRegionScore($worksheet, $dataStartRow, $dataEndRow, $columnMapping);

                    return [
                        'worksheet' => $worksheet,
                        'sheet_name' => $sheetName,
                        'header_row' => $row,
                        'start_row' => $dataStartRow,
                        'end_row' => $dataEndRow,
                        'columns' => $columnMapping,
                        'score' => $score
                    ];
                }
            }
        }

        return null;
    }

    /**
     * Detect student columns in a specific row
     */
    private function detectStudentColumnsInRow($worksheet, $row, $maxCol)
    {
        $mapping = [
            'nis' => null,
            'name' => null,
            'address' => null,
            'phone' => null,
            'status' => null,
        ];

        for ($col = 1; $col <= $maxCol; $col++) {
            $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
            $cell = $worksheet->getCell($columnLetter . $row);
            $headerValue = $cell->getValue();

            // Handle formatted/rich text
            if ($headerValue instanceof \PhpOffice\PhpSpreadsheet\RichText\RichText) {
                $headerValue = $headerValue->getPlainText();
            }

            // Normalize
            $normalized = preg_replace('/\s+/', ' ', strtolower(trim($headerValue ?? '')));
            $normalized = preg_replace('/[^a-z0-9\s]/', '', $normalized);

            // Match columns
            if (!$mapping['nis'] && preg_match('/\b(nis|nomor\s*induk|no\s*induk|student\s*id|id\s*siswa)\b/', $normalized)) {
                $mapping['nis'] = $columnLetter;
            }
            if (!$mapping['name'] && preg_match('/\bnama\b|\bname\b|\bfull\s*name\b|\bstudent\s*name\b/', $normalized)) {
                $mapping['name'] = $columnLetter;
            }
            if (!$mapping['address'] && preg_match('/\balamat\b|\baddress\b|\baddr\b/', $normalized)) {
                $mapping['address'] = $columnLetter;
            }
            if (!$mapping['phone'] && preg_match('/\b(hp|phone|telepon|telp|contact|no\s*hp|nomor\s*hp)\b/', $normalized)) {
                $mapping['phone'] = $columnLetter;
            }
            if (!$mapping['status'] && preg_match('/\b(status|state|kondisi)\b/', $normalized)) {
                $mapping['status'] = $columnLetter;
            }
        }

        return $mapping;
    }

    /**
     * Find the last row with data
     */
    private function findDataEndRow($worksheet, $startRow, $maxRow, $columnMapping)
    {
        $emptyRowCount = 0;
        $lastDataRow = $startRow - 1;

        for ($row = $startRow; $row <= $maxRow; $row++) {
            $nis = trim($worksheet->getCell($columnMapping['nis'] . $row)->getValue() ?? '');
            $name = trim($worksheet->getCell($columnMapping['name'] . $row)->getValue() ?? '');

            if (!empty($nis) || !empty($name)) {
                $lastDataRow = $row;
                $emptyRowCount = 0;
            } else {
                $emptyRowCount++;

                // Stop if 5 consecutive empty rows
                if ($emptyRowCount >= 5) {
                    break;
                }
            }
        }

        return $lastDataRow;
    }

    /**
     * Calculate quality score for data region
     */
    private function calculateDataRegionScore($worksheet, $startRow, $endRow, $columnMapping)
    {
        $score = 0;
        $rowCount = 0;
        $validRows = 0;

        // Sample up to 10 rows
        $sampleRows = min(10, $endRow - $startRow + 1);
        $step = max(1, floor(($endRow - $startRow + 1) / $sampleRows));

        for ($row = $startRow; $row <= $endRow && $rowCount < $sampleRows; $row += $step) {
            $rowCount++;
            $nis = trim($worksheet->getCell($columnMapping['nis'] . $row)->getValue() ?? '');
            $name = trim($worksheet->getCell($columnMapping['name'] . $row)->getValue() ?? '');

            if (!empty($nis) && !empty($name)) {
                $validRows++;
            }
        }

        // Base score: percentage of valid rows
        $score = ($validRows / max(1, $rowCount)) * 100;

        // Bonus for having optional columns
        if ($columnMapping['address']) $score += 5;
        if ($columnMapping['phone']) $score += 5;
        if ($columnMapping['status']) $score += 5;

        // Bonus for more data rows
        $totalRows = $endRow - $startRow + 1;
        if ($totalRows >= 10) $score += 10;
        if ($totalRows >= 50) $score += 10;

        return $score;
    }

    /**
     * Generate username from full name and class
     * Example: "Alika Azalea Qusara" + "1.1" = "alikaazalea11"
     */
    private function generateUsername($fullName, $className = null)
    {
        // Get first 2 words
        $words = explode(' ', $fullName);
        $firstTwoWords = array_slice($words, 0, 2);
        $username = strtolower(implode('', $firstTwoWords));

        // Remove special characters
        $username = preg_replace('/[^a-z0-9]/', '', $username);

        // Add class number if available
        if ($className) {
            $classNumber = preg_replace('/[^0-9]/', '', $className);
            $username .= $classNumber;
        }

        return $username;
    }

    /**
     * Get SPP Card (Kartu SPP Digital) for a student
     * Shows monthly payment status with color indicators
     */
    public function sppCard($id, Request $request)
    {
        try {
            $user = Auth::user();

            // If user is student, only allow access to their own SPP card
            if ($user->role->name === 'student') {
                if ($user->student_id != $id) {
                    return ApiResponse::error('Forbidden: You can only access your own SPP card', 403);
                }
            }

            $student = Student::with(['currentClass', 'user'])->findOrFail($id);

            // Get academic year (default to active one if not specified)
            $academicYearId = $request->get('academic_year_id');

            if (!$academicYearId) {
                $activeYear = \App\Models\AcademicYear::where('is_active', true)->first();
                $academicYearId = $activeYear ? $activeYear->id : null;
            }

            if (!$academicYearId) {
                return ApiResponse::error('Academic year not found', 404);
            }

            $academicYear = \App\Models\AcademicYear::findOrFail($academicYearId);

            // Get all invoices for this student in this academic year
            $invoices = \App\Models\Invoice::with(['items.feeCategory', 'payments'])
                ->where('student_id', $student->id)
                ->where('academic_year_id', $academicYearId)
                ->get();

            // Prepare monthly status (assuming SPP is monthly)
            // We'll extract month from due_date
            $monthlyStatus = [];
            $monthsMap = [
                1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
            ];

            // Group invoices by month (based on due_date)
            foreach ($invoices as $invoice) {
                $month = (int) date('n', strtotime($invoice->due_date));
                $year = (int) date('Y', strtotime($invoice->due_date));

                // Determine status and color
                $status = $invoice->status;
                $statusColor = 'red'; // default unpaid

                if ($status === 'paid') {
                    $statusColor = 'green';
                } elseif ($status === 'partial') {
                    $statusColor = 'yellow';
                }

                // Get payment date (if paid)
                $paymentDate = null;
                if ($invoice->payments->count() > 0) {
                    $lastPayment = $invoice->payments->sortByDesc('payment_date')->first();
                    $paymentDate = $lastPayment->payment_date;
                }

                // Calculate progress percentage
                $progressPercentage = $invoice->total_amount > 0
                    ? round(($invoice->paid_amount / $invoice->total_amount) * 100, 2)
                    : 0;

                $monthlyStatus[] = [
                    'month' => $month,
                    'month_name' => $monthsMap[$month],
                    'year' => $year,
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'amount' => (float) $invoice->total_amount,
                    'paid_amount' => (float) $invoice->paid_amount,
                    'remaining_amount' => (float) ($invoice->total_amount - $invoice->paid_amount),
                    'status' => $status,
                    'status_color' => $statusColor,
                    'progress_percentage' => $progressPercentage,
                    'due_date' => $invoice->due_date,
                    'payment_date' => $paymentDate,
                    'items' => $invoice->items->map(function ($item) {
                        return [
                            'category' => $item->feeCategory->name,
                            'description' => $item->description,
                            'amount' => (float) $item->amount,
                        ];
                    }),
                ];
            }

            // Sort by month
            usort($monthlyStatus, function ($a, $b) {
                return $a['month'] <=> $b['month'];
            });

            // Calculate summary
            $totalMonths = count($monthlyStatus);
            $paidMonths = collect($monthlyStatus)->where('status', 'paid')->count();
            $partialMonths = collect($monthlyStatus)->where('status', 'partial')->count();
            $unpaidMonths = collect($monthlyStatus)->where('status', 'unpaid')->count();

            $totalAmount = collect($monthlyStatus)->sum('amount');
            $totalPaidAmount = collect($monthlyStatus)->sum('paid_amount');
            $totalRemaining = $totalAmount - $totalPaidAmount;
            $overallPercentage = $totalAmount > 0 ? round(($totalPaidAmount / $totalAmount) * 100, 2) : 0;

            return ApiResponse::success([
                'student' => [
                    'id' => $student->id,
                    'nis' => $student->nis,
                    'full_name' => $student->full_name,
                    'class' => $student->currentClass?->name ?? 'N/A',
                ],
                'academic_year' => [
                    'id' => $academicYear->id,
                    'name' => $academicYear->name,
                ],
                'monthly_status' => $monthlyStatus,
                'summary' => [
                    'total_months' => $totalMonths,
                    'paid_months' => $paidMonths,
                    'partial_months' => $partialMonths,
                    'unpaid_months' => $unpaidMonths,
                    'total_amount' => (float) $totalAmount,
                    'paid_amount' => (float) $totalPaidAmount,
                    'remaining' => (float) $totalRemaining,
                    'percentage' => $overallPercentage,
                ],
            ], 'SPP Card fetched successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to fetch SPP card: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get SPP Card for current logged in student (Wali Murid)
     */
    public function mySppCard(Request $request)
    {
        try {
            $user = Auth::user();

            // Check if user has student_id (is a student/wali murid)
            if (!$user->student_id) {
                return ApiResponse::error('User is not associated with a student', 403);
            }

            return $this->sppCard($user->student_id, $request);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to fetch SPP card: ' . $e->getMessage(), 500);
        }
    }
}
