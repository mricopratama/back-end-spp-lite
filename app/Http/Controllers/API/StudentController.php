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
use App\Http\Requests\BulkPromoteAutoRequest;
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

            // Enhanced search: name, NIS, address, phone (case-insensitive)
            if ($request->has('search')) {
                $search = strtolower($request->search);
                $query->where(function ($q) use ($search) {
                    $q->whereRaw('LOWER(full_name) LIKE ?', ["%{$search}%"])
                      ->orWhereRaw('LOWER(nis) LIKE ?', ["%{$search}%"])
                      ->orWhereRaw('LOWER(address) LIKE ?', ["%{$search}%"])
                      ->orWhereRaw('LOWER(phone_number) LIKE ?', ["%{$search}%"]);
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
                'level' => 'nullable|integer|min:1|max:6',
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

            // Gabungkan filter academic_year_id/year, class_id, dan level dalam satu whereHas agar filter level hanya berlaku pada class history tahun ajaran yang dicari
            $filterByAcademicYear = null;
            if ($request->has('academic_year_id')) {
                $academicYearId = $request->academic_year_id;
                $filterByAcademicYear = function ($q) use ($academicYearId) {
                    $q->where('academic_year_id', $academicYearId);
                };
            } else if ($request->has('year')) {
                $yearParam = $request->year;
                if (strpos($yearParam, '-') !== false) {
                    $academicYearPattern = str_replace('-', '/', $yearParam);
                    $filterByAcademicYear = function ($q) use ($academicYearPattern) {
                        $q->whereHas('academicYear', function ($qa) use ($academicYearPattern) {
                            $qa->where('name', $academicYearPattern);
                        });
                    };
                } else if (strpos($yearParam, '/') !== false) {
                    $academicYearPattern = $yearParam;
                    $filterByAcademicYear = function ($q) use ($academicYearPattern) {
                        $q->whereHas('academicYear', function ($qa) use ($academicYearPattern) {
                            $qa->where('name', $academicYearPattern);
                        });
                    };
                } else {
                    $academicYearPattern = $yearParam . '/%';
                    $filterByAcademicYear = function ($q) use ($academicYearPattern) {
                        $q->whereHas('academicYear', function ($qa) use ($academicYearPattern) {
                            $qa->where('name', 'like', $academicYearPattern);
                        });
                    };
                }
            }

            if ($request->has('class_id') || $request->has('level') || $filterByAcademicYear) {
                $classId = $request->class_id;
                $level = $request->level;
                $query->whereHas('classHistory', function ($q) use ($classId, $level, $filterByAcademicYear) {
                    if ($filterByAcademicYear) {
                        $filterByAcademicYear($q);
                    }
                    if ($classId) {
                        $q->where('class_id', $classId);
                    }
                    if ($level) {
                        $q->whereHas('class', function ($qc) use ($level) {
                            $qc->where('level', $level);
                        });
                    }
                });
            }

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Search functionality (case-insensitive)
            if ($request->has('search')) {
                $search = strtolower($request->search);
                $query->where(function ($q) use ($search) {
                    $q->whereRaw('LOWER(full_name) LIKE ?', ["%{$search}%"])
                      ->orWhereRaw('LOWER(nis) LIKE ?', ["%{$search}%"])
                      ->orWhereRaw('LOWER(address) LIKE ?', ["%{$search}%"])
                      ->orWhereRaw('LOWER(phone_number) LIKE ?', ["%{$search}%"]);
                });
            }

            // Sorting
            $sortBy = $request->get('sort_by');
            $sortOrder = $request->get('sort_order', 'asc');
            if ($sortBy) {
                $query->orderBy($sortBy, $sortOrder);
            } else {
                // Default: urutkan berdasarkan kelas (level ASC, class_id ASC) pada tahun ajaran yang difilter, lalu nis ASC
                $query->with(['classHistory.class']);
                $query->getQuery()->orders = [];
                $academicYearFilter = '';
                if ($request->has('academic_year_id')) {
                    $academicYearFilter = ' AND sch.academic_year_id = ' . intval($request->academic_year_id);
                }
                $query->orderByRaw('(
                    SELECT c.level FROM student_class_history sch
                    JOIN classes c ON sch.class_id = c.id
                    WHERE sch.student_id = students.id'
                    . $academicYearFilter .
                    ' ORDER BY sch.id DESC LIMIT 1
                ) ASC');
                $query->orderByRaw('(
                    SELECT sch.class_id FROM student_class_history sch
                    WHERE sch.student_id = students.id'
                    . $academicYearFilter .
                    ' ORDER BY sch.id DESC LIMIT 1
                ) ASC');
                $query->orderBy('nis', 'asc');
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $perPage = min($perPage, 100); // Max 100 items per page

            $students = $query->paginate($perPage);

            // Transform data to include current class info more clearly


            // Ambil tahun ajaran dari parameter (year), jika tidak ada fallback ke current_class_info
            $yearParam = $request->year;
            $transformedData = $students->getCollection()->map(function ($student) use ($yearParam) {
                // Cari class history sesuai tahun ajaran yang dipilih
                $history = null;
                if ($yearParam) {
                    $yearPattern = str_replace('-', '/', $yearParam);
                    $history = $student->classHistories()
                        ->whereHas('academicYear', function ($q) use ($yearPattern) {
                            $q->where('name', $yearPattern);
                        })
                        ->with(['class', 'academicYear'])
                        ->first();
                }
                // Jika tidak ditemukan, fallback ke current_class_info
                $classInfo = null;
                if ($history) {
                    $classInfo = [
                        'class_id' => $history->class_id,
                        'class_name' => $history->class->name ?? null,
                        'academic_year_id' => $history->academic_year_id,
                        'academic_year_name' => $history->academicYear->name ?? null,
                    ];
                } else {
                    $classInfo = $student->current_class_info;
                }
                return [
                    'id' => $student->id,
                    'nis' => $student->nis,
                    'full_name' => $student->full_name,
                    // 'address' => $student->address, // intentionally omitted
                    // 'phone_number' => $student->phone_number, // intentionally omitted
                    // 'status' => $student->status,
                    'class_info' => $classInfo,
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

            // Create student
            $studentData = $request->validated();
            $classId = $studentData['class_id'];
            $academicYearId = $studentData['academic_year_id'];

            // Remove class_id and academic_year_id from student data
            unset($studentData['class_id'], $studentData['academic_year_id']);

            $student = Student::create($studentData);

            // Assign class to student
            StudentClassHistory::create([
                'student_id' => $student->id,
                'class_id' => $classId,
                'academic_year_id' => $academicYearId,
            ]);

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

            // Validasi: hanya siswa dengan status 'active' di tahun ajaran aktif yang bisa diedit
            $activeYear = \App\Models\AcademicYear::where('is_active', true)->first();
            if ($activeYear) {
                $classHistory = $student->classHistories()->where('academic_year_id', $activeYear->id)->first();
                if ($student->status !== 'active') {
                    DB::rollBack();
                    return ApiResponse::error('Hanya siswa dengan status ACTIVE di tahun ajaran aktif yang bisa diedit.', 403);
                }
            }

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

            // Check if student has invoice items
            if ($student->invoice_items()->exists()) {
                return ApiResponse::error(
                    'Cannot delete student with existing invoice items. Set status to INACTIVE instead.',
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
     * Preview bulk promotion with automatic 1:1 class mapping
     * GET /api/students/bulk-promote/preview?from_academic_year_id=7&to_academic_year_id=8
     */
    public function bulkPromotePreview(Request $request)
    {
        try {
            $request->validate([
                'from_academic_year_id' => 'required|exists:academic_years,id',
                'to_academic_year_id' => 'required|exists:academic_years,id|different:from_academic_year_id',
            ]);

            $fromYearId = $request->from_academic_year_id;
            $toYearId = $request->to_academic_year_id;

            // Get all ACTIVE students in the source academic year
            $studentHistories = StudentClassHistory::with(['student', 'class'])
                ->where('academic_year_id', $fromYearId)
                ->whereHas('student', function($q) {
                    $q->where('status', 'active');
                })
                ->get()
                ->groupBy('class_id');

            $classMapping = [];
            $totalPromote = 0;
            $totalGraduate = 0;
            $warnings = [];

            foreach ($studentHistories as $classId => $histories) {
                $sourceClass = $histories->first()->class;
                $studentCount = $histories->count();

                // Check if students already promoted
                $alreadyPromoted = StudentClassHistory::whereIn('student_id', $histories->pluck('student_id'))
                    ->where('academic_year_id', $toYearId)
                    ->count();

                if ($alreadyPromoted > 0) {
                    $warnings[] = "{$alreadyPromoted} siswa dari {$sourceClass->name} sudah dipromosikan ke tahun ajaran tujuan";
                }

                // Determine action based on level
                if ($sourceClass->level >= 6) {
                    // GRADUATE
                    $classMapping[] = [
                        'from_class_id' => $sourceClass->id,
                        'from_class_name' => $sourceClass->name,
                        'from_class_level' => $sourceClass->level,
                        'to_class_id' => null,
                        'to_class_name' => null,
                        'to_class_level' => null,
                        'student_count' => $studentCount,
                        'action' => 'graduate'
                    ];
                    $totalGraduate += $studentCount;
                } else {
                    // PROMOTE - Find target class with auto 1:1 mapping
                    $targetClass = $this->findTargetClass($sourceClass);

                    if (!$targetClass) {
                        $warnings[] = "Kelas tujuan untuk '{$sourceClass->name}' tidak ditemukan (level " . ($sourceClass->level + 1) . ")";
                    }

                    $classMapping[] = [
                        'from_class_id' => $sourceClass->id,
                        'from_class_name' => $sourceClass->name,
                        'from_class_level' => $sourceClass->level,
                        'to_class_id' => $targetClass?->id,
                        'to_class_name' => $targetClass?->name,
                        'to_class_level' => $targetClass?->level,
                        'student_count' => $studentCount,
                        'action' => 'promote'
                    ];
                    $totalPromote += $studentCount;
                }
            }

            // Count inactive students
            $inactiveCount = Student::where('status', '!=', 'active')
                ->whereHas('classHistory', function($q) use ($fromYearId) {
                    $q->where('academic_year_id', $fromYearId);
                })
                ->count();

            return ApiResponse::success([
                'summary' => [
                    'total_active_students' => $totalPromote + $totalGraduate,
                    'will_promote' => $totalPromote,
                    'will_graduate' => $totalGraduate,
                    'will_skip_inactive' => $inactiveCount,
                ],
                'class_mapping' => $classMapping,
                'warnings' => $warnings,
            ], 'Preview bulk promotion generated successfully');

        } catch (\Exception $e) {
            return ApiResponse::error('Failed to generate preview: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Execute bulk promotion with automatic 1:1 class mapping
     * POST /api/students/bulk-promote/auto
     */
    public function bulkPromoteAuto(BulkPromoteAutoRequest $request)
    {
        try {
            DB::beginTransaction();

            $fromYearId = $request->from_academic_year_id;
            $toYearId = $request->to_academic_year_id;

            $promoted = 0;
            $graduated = 0;
            $skipped = 0;
            $failed = 0;

            $details = [
                'promoted' => [],
                'graduated' => [],
                'skipped' => [],
                'failed' => []
            ];

            // Get all ACTIVE students in the source academic year
            $studentHistories = StudentClassHistory::with(['student', 'class'])
                ->where('academic_year_id', $fromYearId)
                ->whereHas('student', function($q) {
                    $q->where('status', 'active');
                })
                ->get();

            foreach ($studentHistories as $history) {
                $student = $history->student;
                $sourceClass = $history->class;

                // Skip if already promoted
                $exists = StudentClassHistory::where([
                    'student_id' => $student->id,
                    'academic_year_id' => $toYearId,
                ])->exists();

                if ($exists) {
                    $skipped++;
                    $details['skipped'][] = [
                        'student_id' => $student->id,
                        'student_name' => $student->full_name,
                        'student_nis' => $student->nis,
                        'reason' => 'Already promoted to target academic year'
                    ];
                    continue;
                }

                // Determine action based on level
                if ($sourceClass->level >= 6) {
                    // GRADUATE
                    $student->update(['status' => 'graduated']);
                    $graduated++;
                    $details['graduated'][] = [
                        'student_id' => $student->id,
                        'student_name' => $student->full_name,
                        'student_nis' => $student->nis,
                        'from_class' => $sourceClass->name,
                        'status' => 'graduated'
                    ];
                } else {
                    // PROMOTE - Find target class
                    $targetClass = $this->findTargetClass($sourceClass);

                    if (!$targetClass) {
                        $failed++;
                        $details['failed'][] = [
                            'student_id' => $student->id,
                            'student_name' => $student->full_name,
                            'student_nis' => $student->nis,
                            'from_class' => $sourceClass->name,
                            'reason' => "Target class not found for level " . ($sourceClass->level + 1)
                        ];
                        continue;
                    }

                    // Create new class history
                    StudentClassHistory::create([
                        'student_id' => $student->id,
                        'class_id' => $targetClass->id,
                        'academic_year_id' => $toYearId,
                    ]);

                    $promoted++;
                    $details['promoted'][] = [
                        'student_id' => $student->id,
                        'student_name' => $student->full_name,
                        'student_nis' => $student->nis,
                        'from_class' => $sourceClass->name,
                        'to_class' => $targetClass->name
                    ];
                }
            }

            DB::commit();

            return ApiResponse::success([
                'success' => true,
                'promoted_count' => $promoted,
                'graduated_count' => $graduated,
                'skipped_count' => $skipped,
                'failed_count' => $failed,
                'details' => $details,
            ], 'Bulk promotion completed successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::error('Failed to execute bulk promotion: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Find target class for promotion using 1:1 mapping logic
     * Rule: level + 1, same class suffix
     * Example: "Kelas 1.1" -> "Kelas 2.1", "Kelas 3.2" -> "Kelas 4.2"
     */
    private function findTargetClass($sourceClass)
    {
        $targetLevel = $sourceClass->level + 1;

        // Extract class suffix using regex
        // Pattern: "Kelas {level}.{suffix}" -> extract suffix
        preg_match('/\.(\d+)$/', $sourceClass->name, $matches);
        $classSuffix = $matches[1] ?? null;

        // Try to find class with same suffix
        if ($classSuffix) {
            $targetClass = Classes::where('level', $targetLevel)
                ->where('name', 'like', "%.{$classSuffix}")
                ->first();

            if ($targetClass) {
                return $targetClass;
            }
        }

        // Fallback: Get first class at target level
        return Classes::where('level', $targetLevel)
            ->orderBy('name')
            ->first();
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

            // Get all invoice items for this student in this academic year
            $invoiceItems = \App\Models\InvoiceItem::with(['feeCategory', 'payments'])
                ->where('student_id', $student->id)
                ->where('academic_year_id', $academicYearId)
                ->orderBy('period_month', 'asc')
                ->get();

            // Prepare monthly status (assuming SPP is monthly)
            // We'll extract month from period_month
            $monthlyStatus = [];
            $monthsMap = [
                1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
            ];

            // Group invoice items by month (based on period_month)
            foreach ($invoiceItems as $item) {
                $month = $item->period_month;
                // Get year from academic year name (e.g., "2024/2025" -> 2024)
                $yearParts = explode('/', $academicYear->name);
                $year = (int) $yearParts[0];

                // Determine status and color
                $status = $item->status;
                $statusColor = 'red'; // default unpaid

                if ($status === 'paid') {
                    $statusColor = 'green';
                } elseif ($status === 'partial') {
                    $statusColor = 'yellow';
                }

                // Get payment date (if paid)
                $paymentDate = null;
                if ($item->payments->count() > 0) {
                    $lastPayment = $item->payments->sortByDesc('payment_date')->first();
                    $paymentDate = $lastPayment->payment_date;
                }

                // Calculate progress percentage
                $progressPercentage = $item->amount > 0
                    ? round(($item->paid_amount / $item->amount) * 100, 2)
                    : 0;

                $monthlyStatus[] = [
                    'month' => $month,
                    'month_name' => $monthsMap[$month],
                    'year' => $year,
                    'invoice_item_id' => $item->id,
                    'invoice_number' => $item->invoice_number,
                    'amount' => (float) $item->amount,
                    'paid_amount' => (float) $item->paid_amount,
                    'remaining_amount' => (float) ($item->amount - $item->paid_amount),
                    'status' => $status,
                    'status_color' => $statusColor,
                    'progress_percentage' => $progressPercentage,
                    'payment_date' => $paymentDate,
                    'fee_category' => $item->feeCategory->name,
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
