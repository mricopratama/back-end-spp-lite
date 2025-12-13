<?php

namespace App\Http\Controllers\API;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\ClassRequest;
use App\Models\Classes;

class ClassController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $classes = Classes::orderBy('level', 'asc')->get();
        return ApiResponse::success($classes, 'List of classes');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ClassRequest $request)
    {
        $validated = $request->validated();
        $class = Classes::create($validated);

        return ApiResponse::success($class, 'Class created successfully', 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $class = Classes::find($id);

        if (!$class) {
            return ApiResponse::error('Class not found', 404);
        }

        return ApiResponse::success($class);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ClassRequest $request, string $id)
    {
        $class = Classes::find($id);

        if (!$class) {
            return ApiResponse::error('Class not found', 404);
        }

        $validated = $request->validated();
        $class->update($validated);

        return ApiResponse::success($class, 'Class updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $class = Classes::find($id);

        if (!$class) {
            return ApiResponse::error('Class not found', 404);
        }

        // Cannot delete if referenced in student_class_history
        if ($class->studentClassHistories()->exists()) {
            return ApiResponse::error('Cannot delete class or data is in use.', 400);
        }

        $class->delete();

        return ApiResponse::success(null, 'Class deleted successfully');
    }
}
