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
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class StudentController extends Controller
{
    /**
     * Display a listing of students
     */
    public function index(Request $request)
    {
        try {
            $query = Student::query()->with(['currentClass', 'user']);

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

            // Search by name or NIS
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('full_name', 'like', "%{$search}%")
                      ->orWhere('nis', 'like', "%{$search}%");
                });
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
     * Import students from Excel
     */
    public function import(ImportStudentsRequest $request)
    {
        try {
            DB::beginTransaction();

            $file = $request->file('file');
            $spreadsheet = IOFactory::load($file->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            // Expected columns: NIS, Nama, Alamat, No HP, Status
            $header = array_shift($rows); // Remove header row

            $inserted = 0;
            $failed = 0;
            $errors = [];

            foreach ($rows as $index => $row) {
                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }

                try {
                    $rowNumber = $index + 2; // +2 because array starts at 0 and we removed header

                    // Validate required fields
                    if (empty($row[0]) || empty($row[1])) {
                        $errors[] = "Row {$rowNumber}: NIS dan Nama wajib diisi";
                        $failed++;
                        continue;
                    }

                    // Check if NIS already exists
                    if (Student::where('nis', $row[0])->exists()) {
                        $errors[] = "Row {$rowNumber}: NIS {$row[0]} sudah terdaftar";
                        $failed++;
                        continue;
                    }

                    // Create student
                    Student::create([
                        'nis' => $row[0],
                        'full_name' => $row[1],
                        'address' => $row[2] ?? null,
                        'phone_number' => $row[3] ?? null,
                        'status' => $row[4] ?? 'ACTIVE',
                    ]);

                    $inserted++;
                } catch (\Exception $e) {
                    $errors[] = "Row {$rowNumber}: " . $e->getMessage();
                    $failed++;
                }
            }

            DB::commit();

            return ApiResponse::success([
                'total_rows' => count($rows),
                'inserted' => $inserted,
                'failed' => $failed,
                'errors' => $errors,
            ], "Import completed. {$inserted} students inserted, {$failed} failed.");
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::error('Failed to import students: ' . $e->getMessage(), 500);
        }
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
}
