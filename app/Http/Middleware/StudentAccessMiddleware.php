<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use App\Helpers\ApiResponse;

class StudentAccessMiddleware
{
    /**
     * Handle an incoming request.
     * Prevent students from accessing other students' data via nis parameter
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // If user is a student
        if ($user && $user->student_id) {
            // Check if student is trying to use nis parameter
            if ($request->has('nis')) {
                // Get the student's own NIS
                $userNis = $user->student ? $user->student->nis : null;

                // If provided nis doesn't match their own, block it
                if ($request->nis !== $userNis) {
                    return ApiResponse::error('Forbidden: You can only access your own data', 403);
                }
            }
        }

        return $next($request);
    }
}
