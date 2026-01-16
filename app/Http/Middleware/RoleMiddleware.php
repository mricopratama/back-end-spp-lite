<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use App\Helpers\ApiResponse;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = Auth::user();

        if (!$user) {
            return ApiResponse::error('Unauthorized', 401);
        }

        // Check if user has role relationship loaded
        if (!$user->relationLoaded('role')) {
            $user->load('role');
        }

        // Get the role name from the user's role relationship
        if (!$user->role) {
            return ApiResponse::error('User role not found. Please contact administrator.', 403);
        }

        $userRole = $user->role->name;

        if ($userRole !== $role) {
            return ApiResponse::error('Forbidden: Insufficient permissions', 403);
        }

        return $next($request);
    }
}
