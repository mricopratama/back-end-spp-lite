<?php

namespace App\Http\Controllers\API;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\FeeCategoryRequest;
use App\Models\FeeCategory;

class FeeCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $feeCategories = FeeCategory::orderBy('name', 'asc')->get();
        return ApiResponse::success($feeCategories, 'List of fee categories');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(FeeCategoryRequest $request)
    {
        $validated = $request->validated();
        $feeCategory = FeeCategory::create($validated);

        return ApiResponse::success($feeCategory, 'Fee category created successfully', 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $feeCategory = FeeCategory::find($id);

        if (!$feeCategory) {
            return ApiResponse::error('Fee category not found', 404);
        }

        return ApiResponse::success($feeCategory);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(FeeCategoryRequest $request, string $id)
    {
        $feeCategory = FeeCategory::find($id);

        if (!$feeCategory) {
            return ApiResponse::error('Fee category not found', 404);
        }

        $validated = $request->validated();
        $feeCategory->update($validated);

        return ApiResponse::success($feeCategory, 'Fee category updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $feeCategory = FeeCategory::find($id);

        if (!$feeCategory) {
            return ApiResponse::error('Fee category not found', 404);
        }

        // Cannot delete if referenced in invoice_items
        if ($feeCategory->invoiceItems()->exists()) {
            return ApiResponse::error('Cannot delete fee category or data is in use.', 400);
        }

        $feeCategory->delete();

        return ApiResponse::success(null, 'Fee category deleted successfully');
    }
}
