# Authentication System Documentation

## Overview

This document describes the authentication system implementation for SPP-Lite application with role-based access control for Admin and Student roles.

## Components Implemented

### 1. Database Schema

#### Roles Table
- `id`: Primary key
- `name`: Role name ('admin', 'student')
- `description`: Role description
- `created_at`, `updated_at`: Timestamps

#### Users Table
- `id`: Primary key
- `username`: Unique username
- `password_hash`: Password hash (using `password_hash()`)
- `full_name`: User's full name
- `role_id`: Foreign key to roles table
- `student_id`: Foreign key to students table (nullable, for student users)
- `created_at`, `updated_at`: Timestamps

### 2. Database Seeder

**RoleSeeder.php** creates:
- 2 roles: admin (id: 1) and student (id: 2)
- Default admin user:
  - Username: `admin`
  - Password: `password`
  - Full name: `Administrator Sekolah`
  - Role: admin
  - Student ID: null

### 3. Authentication Controller

**AuthController.php** (`app/Http/Controllers/API/AuthController.php`)

#### Endpoints:

##### POST /api/auth/login
Authenticate user and generate Sanctum token.

**Request:**
```json
{
  "username": "admin",
  "password": "password"
}
```

**Response (200 OK):**
```json
{
  "meta": {
    "code": 200,
    "status": "success",
    "message": "Login successful"
  },
  "data": {
    "token": "1|xxxxx",
    "user": {
      "id": 1,
      "username": "admin",
      "full_name": "Administrator Sekolah",
      "role": "admin",
      "student_id": null
    }
  }
}
```

**Response (401 Unauthorized):**
```json
{
  "meta": {
    "code": 401,
    "status": "error",
    "message": "Invalid credentials"
  },
  "data": null
}
```

##### GET /api/auth/me
Get authenticated user profile (requires authentication).

**Headers:**
```
Authorization: Bearer {token}
```

**Response (Admin):**
```json
{
  "meta": {
    "code": 200,
    "status": "success",
    "message": "Success"
  },
  "data": {
    "id": 1,
    "username": "admin",
    "full_name": "Administrator Sekolah",
    "role": "admin"
  }
}
```

**Response (Student):**
```json
{
  "meta": {
    "code": 200,
    "status": "success",
    "message": "Success"
  },
  "data": {
    "id": 5,
    "username": "1001",
    "full_name": "Budi Santoso",
    "role": "student",
    "student_detail": {
      "nis": "1001",
      "class_name": "Kelas 1",
      "status": "ACTIVE"
    }
  }
}
```

##### POST /api/auth/logout
Revoke current authentication token (requires authentication).

**Headers:**
```
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "meta": {
    "code": 200,
    "status": "success",
    "message": "Success"
  },
  "data": {
    "message": "Logged out successfully"
  }
}
```

##### PUT /api/auth/change-password
Change user password (requires authentication).

**Headers:**
```
Authorization: Bearer {token}
```

**Request:**
```json
{
  "old_password": "password",
  "new_password": "newpass123",
  "new_password_confirmation": "newpass123"
}
```

**Response (200 OK):**
```json
{
  "meta": {
    "code": 200,
    "status": "success",
    "message": "Success"
  },
  "data": {
    "message": "Password changed successfully"
  }
}
```

**Response (400 Bad Request - Invalid old password):**
```json
{
  "meta": {
    "code": 400,
    "status": "error",
    "message": "Old password is incorrect"
  },
  "data": null
}
```

### 4. Form Request Validation

#### LoginRequest
- `username`: required|string
- `password`: required|string

#### ChangePasswordRequest
- `old_password`: required|string
- `new_password`: required|string|min:6|confirmed

### 5. Middleware

#### RoleMiddleware
Checks if authenticated user has the required role.

**Usage:**
```php
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    // Admin-only routes
});
```

**Response (403 Forbidden):**
```json
{
  "meta": {
    "code": 403,
    "status": "error",
    "message": "Forbidden"
  },
  "data": null
}
```

## Security Features

1. **Password Hashing**: Uses PHP's `password_hash()` with `PASSWORD_DEFAULT` algorithm
2. **Password Verification**: Uses `password_verify()` for secure password checking
3. **Token-based Authentication**: Laravel Sanctum for API authentication
4. **Role-based Access Control**: RoleMiddleware for protecting routes
5. **Input Validation**: Form Request classes for validating user input
6. **API Response Format**: Consistent ApiResponse helper for all responses

## Testing

Comprehensive test suite with 11 authentication tests:
- Login with valid credentials
- Login with invalid credentials
- Login with missing fields (validation)
- Get authenticated user profile
- Get student user profile with details
- Get profile without token
- Logout and revoke token
- Change password with valid old password
- Change password with invalid old password
- Change password with mismatched confirmation
- Change password with short password

All tests passing: **11/11 (55 assertions)**

## Setup Instructions

### 1. Install Dependencies
```bash
composer install
```

### 2. Configure Environment
```bash
cp .env.example .env
php artisan key:generate
```

### 3. Run Migrations and Seeders
```bash
php artisan migrate:fresh --seed
```

### 4. Start Development Server
```bash
php artisan serve
```

### 5. Test Authentication
```bash
# Login
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"username":"admin","password":"password"}'

# Get Profile
curl -X GET http://localhost:8000/api/auth/me \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {token}"
```

## API Response Format

All API responses follow this format using the `ApiResponse` helper:

**Success Response:**
```json
{
  "meta": {
    "code": 200,
    "status": "success",
    "message": "Success message"
  },
  "data": {
    // Response data
  }
}
```

**Error Response:**
```json
{
  "meta": {
    "code": 400,
    "status": "error",
    "message": "Error message"
  },
  "data": null
}
```

## Notes

- Student users must have `student_id` populated to link to their student record
- Admin users have `student_id = NULL`
- Password minimum length: 6 characters
- All protected routes require `Authorization: Bearer {token}` header
- Token is revoked on logout
- Student profile includes current class information from active academic year
