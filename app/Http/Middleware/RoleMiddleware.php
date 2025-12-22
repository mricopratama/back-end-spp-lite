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

        // Get the role name from the user's role relationship
        $userRole = $user->role->name ?? null;

        if ($userRole !== $role) {
            return ApiResponse::error('Forbidden', 403);
        }

        return $next($request);
    }
}
