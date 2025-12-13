<?php

namespace App\Http\Controllers\API;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\AcademicYearRequest;
use App\Models\AcademicYear;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AcademicYearController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $academicYears = AcademicYear::orderBy('name', 'desc')->get();
        return ApiResponse::success($academicYears, 'List of academic years');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AcademicYearRequest $request)
    {
        $validated = $request->validated();

        DB::beginTransaction();
        try {
            // If is_active is true, set all others to false
            if ($validated['is_active']) {
                AcademicYear::where('is_active', true)->update(['is_active' => false]);
            }

            $academicYear = AcademicYear::create($validated);

            DB::commit();
            return ApiResponse::success($academicYear, 'Academic year created successfully', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create academic year: ' . $e->getMessage());
            return ApiResponse::error('Failed to create academic year', 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $academicYear = AcademicYear::find($id);

        if (!$academicYear) {
            return ApiResponse::error('Academic year not found', 404);
        }

        return ApiResponse::success($academicYear);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(AcademicYearRequest $request, string $id)
    {
        $academicYear = AcademicYear::find($id);

        if (!$academicYear) {
            return ApiResponse::error('Academic year not found', 404);
        }

        $validated = $request->validated();

        DB::beginTransaction();
        try {
            // If is_active is true, set all others to false
            if ($validated['is_active']) {
                AcademicYear::where('id', '!=', $id)
                    ->where('is_active', true)
                    ->update(['is_active' => false]);
            }

            $academicYear->update($validated);

            DB::commit();
            return ApiResponse::success($academicYear, 'Academic year updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update academic year: ' . $e->getMessage());
            return ApiResponse::error('Failed to update academic year', 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $academicYear = AcademicYear::find($id);

        if (!$academicYear) {
            return ApiResponse::error('Academic year not found', 404);
        }

        // Cannot delete if is_active is true
        if ($academicYear->is_active) {
            return ApiResponse::error('Cannot delete active academic year or data is in use.', 400);
        }

        // Cannot delete if referenced in student_class_history
        if ($academicYear->studentClassHistories()->exists()) {
            return ApiResponse::error('Cannot delete active academic year or data is in use.', 400);
        }

        $academicYear->delete();

        return ApiResponse::success(null, 'Academic year deleted successfully');
    }
}
