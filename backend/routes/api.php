<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AdminRoleController;
use App\Http\Controllers\Api\AlumniVerificationController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/stores/{id}', [\App\Http\Controllers\Api\StoreController::class, 'show']);

// Email verification public link
Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])->name('verification.verify');

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    
    // Email verification resend
    Route::post('/email/resend', [AuthController::class, 'resendVerificationEmail']);

    // Store Owner routes (requires verified alumni status)
    Route::middleware('verified_alumni')->group(function () {
        Route::post('/stores', [\App\Http\Controllers\Api\StoreController::class, 'register']);
        Route::get('/stores/my-store', [\App\Http\Controllers\Api\StoreController::class, 'myStore']);
        Route::put('/stores/my-store', [\App\Http\Controllers\Api\StoreController::class, 'updateMyStore']);
        Route::post('/stores/my-store/logo', [\App\Http\Controllers\Api\StoreController::class, 'uploadLogo']);
        Route::post('/stores/my-store/banner', [\App\Http\Controllers\Api\StoreController::class, 'uploadBanner']);
    });

    // Admin routes
    Route::middleware('admin')->group(function () {
        // Roles & Permissions CRUD
        Route::get('/admin/roles', [AdminRoleController::class, 'getRoles']);
        Route::post('/admin/roles', [AdminRoleController::class, 'createRole']);
        Route::put('/admin/roles/{id}', [AdminRoleController::class, 'updateRole']);
        Route::delete('/admin/roles/{id}', [AdminRoleController::class, 'deleteRole']);
        Route::get('/admin/permissions', [AdminRoleController::class, 'getPermissions']);
        Route::post('/admin/users/{id}/assign-role', [AdminRoleController::class, 'assignRole']);
        
        // Alumni Verification CRUD & Excel Import
        Route::post('/admin/alumni/import', [AlumniVerificationController::class, 'import']);
        Route::get('/admin/alumni', [AlumniVerificationController::class, 'index']);
        Route::get('/admin/alumni/{id}', [AlumniVerificationController::class, 'show']);
        Route::post('/admin/alumni/{id}/verify', [AlumniVerificationController::class, 'verify']);

        // Admin Store Management
        Route::get('/admin/stores', [\App\Http\Controllers\Api\AdminStoreController::class, 'index']);
        Route::post('/admin/stores/{id}/verify', [\App\Http\Controllers\Api\AdminStoreController::class, 'verify']);
    });
});
