<?php

namespace App\Helpers;

class ApiResponse
{
    /**
     * Success response dengan format meta + data
     *
     * @param  mixed  $data
     * @return \Illuminate\Http\JsonResponse
     */
    public static function success($data = null, string $message = 'Success', int $code = 200)
    {
        return response()->json([
            'meta' => [
                'code' => $code,
                'status' => 'success',
                'message' => $message,
            ],
            'data' => $data,
        ], $code);
    }

    /**
     * Error response dengan format meta + data
     *
     * @param  mixed  $errors
     * @return \Illuminate\Http\JsonResponse
     */
    public static function error(string $message = 'Error', int $code = 400, $errors = null)
    {
        return response()->json([
            'meta' => [
                'code' => $code,
                'status' => 'error',
                'message' => $message,
            ],
            'data' => $errors,
        ], $code);
    }

    /**
     * Format pagination response dengan struktur standar
     *
     * @param  \Illuminate\Pagination\LengthAwarePaginator  $paginator
     * @param  array  $filters
     * @return array
     */
    public static function formatPagination($paginator, array $filters = [])
    {
        return [
            'data' => $paginator->items(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                'filters' => $filters,
            ],
        ];
    }
}
