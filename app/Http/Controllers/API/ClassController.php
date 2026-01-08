<?php

namespace App\Http\Controllers\API;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\ClassRequest;
use App\Http\Requests\UpdateClassRequest;
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

        // Auto-generate name based on level
        $level = $validated['level'];

        // Get the last class with the same level
        $lastClass = Classes::where('level', $level)
            ->where('name', 'like', $level . '.%')
            ->orderByRaw('CAST(SUBSTRING_INDEX(name, ".", -1) AS UNSIGNED) DESC')
            ->first();

        if ($lastClass) {
            // Extract the number after the dot and increment
            $parts = explode('.', $lastClass->name);
            $lastNumber = intval(end($parts));
            $validated['name'] = $level . '.' . ($lastNumber + 1);
        } else {
            // First class for this level
            $validated['name'] = $level . '.1';
        }

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
     * Auto-generate ulang nama kelas berdasarkan level baru.
     */
    public function update(UpdateClassRequest $request, string $id)
    {
        $class = Classes::find($id);

        if (!$class) {
            return ApiResponse::error('Class not found', 404);
        }

        $validated = $request->validated();
        $newLevel = $validated['level'];

        // Auto-generate name berdasarkan level baru
        $lastClass = Classes::where('level', $newLevel)
            ->where('id', '!=', $id) // Exclude kelas yang sedang diupdate
            ->where('name', 'like', $newLevel . '.%')
            ->orderByRaw('CAST(SUBSTRING_INDEX(name, ".", -1) AS UNSIGNED) DESC')
            ->first();

        if ($lastClass) {
            // Extract the number after the dot and increment
            $parts = explode('.', $lastClass->name);
            $lastNumber = intval(end($parts));
            $newName = $newLevel . '.' . ($lastNumber + 1);
        } else {
            // First class for this level
            $newName = $newLevel . '.1';
        }

        // Update level dan name
        $class->update([
            'level' => $newLevel,
            'name' => $newName
        ]);

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
