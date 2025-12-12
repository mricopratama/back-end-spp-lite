<?php

namespace App\Http\Controllers\API;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\ChangePasswordRequest;
use App\Models\User;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    /**
     * Login user and generate Sanctum token
     *
     * @param LoginRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(LoginRequest $request)
    {
        $credentials = $request->validated();

        // Find user by username
        $user = User::with('role')->where('username', $credentials['username'])->first();

        // Validate credentials
        if (!$user || !password_verify($credentials['password'], $user->password_hash)) {
            return ApiResponse::error('Invalid credentials', 401);
        }

        // Generate Sanctum token
        $token = $user->createToken('auth_token')->plainTextToken;

        // Prepare user data
        $userData = [
            'id' => $user->id,
            'username' => $user->username,
            'full_name' => $user->full_name,
            'role' => $user->role->name,
            'student_id' => $user->student_id,
        ];

        return ApiResponse::success([
            'token' => $token,
            'user' => $userData,
        ], 'Login successful');
    }

    /**
     * Get authenticated user profile
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function me(Request $request)
    {
        $user = $request->user();
        $user->load('role', 'student');

        $userData = [
            'id' => $user->id,
            'username' => $user->username,
            'full_name' => $user->full_name,
            'role' => $user->role->name,
        ];

        // Include student details if user is a student
        if ($user->role->name === 'student' && $user->student) {
            // Get current class information using Eloquent relationships
            $currentClassHistory = $user->student->classHistories()
                ->whereHas('academicYear', function ($query) {
                    $query->where('is_active', true);
                })
                ->with(['class', 'academicYear'])
                ->first();

            $userData['student_detail'] = [
                'nis' => $user->student->nis,
                'class_name' => $currentClassHistory?->class->name,
                'status' => strtoupper($user->student->status),
            ];
        }

        return ApiResponse::success($userData);
    }

    /**
     * Logout user and revoke current token
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        // Revoke current token
        $request->user()->currentAccessToken()->delete();

        return ApiResponse::success(['message' => 'Logged out successfully']);
    }

    /**
     * Change user password
     *
     * @param ChangePasswordRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function changePassword(ChangePasswordRequest $request)
    {
        $user = $request->user();
        $validated = $request->validated();

        // Validate old password
        if (!password_verify($validated['old_password'], $user->password_hash)) {
            return ApiResponse::error('Old password is incorrect', 400);
        }

        // Update password
        $user->password_hash = password_hash($validated['new_password'], PASSWORD_DEFAULT);
        $user->save();

        return ApiResponse::success(['message' => 'Password changed successfully']);
    }
}
