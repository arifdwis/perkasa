<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AdminRoleController;
use Illuminate\Support\Facades\Route;

// Public auth routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Email verification public link
Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])->name('verification.verify');

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    
    // Email verification resend
    Route::post('/email/resend', [AuthController::class, 'resendVerificationEmail']);

    // Admin routes
    Route::middleware('admin')->group(function () {
        Route::get('/admin/roles', [AdminRoleController::class, 'getRoles']);
        Route::post('/admin/roles', [AdminRoleController::class, 'createRole']);
        Route::put('/admin/roles/{id}', [AdminRoleController::class, 'updateRole']);
        Route::delete('/admin/roles/{id}', [AdminRoleController::class, 'deleteRole']);
        Route::get('/admin/permissions', [AdminRoleController::class, 'getPermissions']);
        Route::post('/admin/users/{id}/assign-role', [AdminRoleController::class, 'assignRole']);
    });
});
