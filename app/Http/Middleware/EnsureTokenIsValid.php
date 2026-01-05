<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureTokenIsValid
{
    /**
     * Handle an incoming request for Sanctum auth.
     * This middleware authenticates user via Sanctum and returns JSON 401 if unauthorized.
     */
    public function handle(Request $request, Closure $next)
    {
        // Authenticate user using Sanctum
        $user = Auth::guard('sanctum')->user();

        if (!$user) {
            return response()->json([
                'meta' => [
                    'code' => 401,
                    'status' => 'error',
                    'message' => 'Unauthorized',
                ],
                'data' => null,
            ], 401);
        }

        // Set the authenticated user to the request
        Auth::setUser($user);
        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}
