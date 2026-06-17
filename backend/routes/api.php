<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AdminRoleController;
use App\Http\Controllers\Api\AlumniVerificationController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/stores/{id}', [\App\Http\Controllers\Api\StoreController::class, 'show']);
Route::get('/product-categories', [\App\Http\Controllers\Api\ProductCategoryController::class, 'index']);
Route::get('/service-categories', [\App\Http\Controllers\Api\ServiceCategoryController::class, 'index']);
Route::get('/products', [\App\Http\Controllers\Api\ProductController::class, 'index']);
Route::get('/products/{slug}', [\App\Http\Controllers\Api\ProductController::class, 'show']);
Route::get('/services', [\App\Http\Controllers\Api\ServiceController::class, 'index']);
Route::get('/services/{slug}', [\App\Http\Controllers\Api\ServiceController::class, 'show']);
Route::get('/catalog', [\App\Http\Controllers\Api\CatalogController::class, 'index']);

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

        // Seller Products
        Route::get('/seller/products', [\App\Http\Controllers\Api\ProductController::class, 'sellerProducts']);
        Route::post('/seller/products', [\App\Http\Controllers\Api\ProductController::class, 'store']);
        Route::get('/seller/products/{id}', [\App\Http\Controllers\Api\ProductController::class, 'sellerShow']);
        Route::put('/seller/products/{id}', [\App\Http\Controllers\Api\ProductController::class, 'update']);
        Route::delete('/seller/products/{id}', [\App\Http\Controllers\Api\ProductController::class, 'destroy']);
        Route::post('/seller/products/{id}/image', [\App\Http\Controllers\Api\ProductController::class, 'uploadImage']);
        Route::post('/seller/products/{id}/gallery', [\App\Http\Controllers\Api\ProductController::class, 'uploadGallery']);
        Route::delete('/seller/products/{productId}/images/{imageId}', [\App\Http\Controllers\Api\ProductController::class, 'deleteImage']);

        // Seller Services
        Route::get('/seller/services', [\App\Http\Controllers\Api\ServiceController::class, 'sellerServices']);
        Route::post('/seller/services', [\App\Http\Controllers\Api\ServiceController::class, 'store']);
        Route::get('/seller/services/{id}', [\App\Http\Controllers\Api\ServiceController::class, 'sellerShow']);
        Route::put('/seller/services/{id}', [\App\Http\Controllers\Api\ServiceController::class, 'update']);
        Route::delete('/seller/services/{id}', [\App\Http\Controllers\Api\ServiceController::class, 'destroy']);
        Route::post('/seller/services/{id}/image', [\App\Http\Controllers\Api\ServiceController::class, 'uploadImage']);
        Route::post('/seller/services/{id}/portfolio', [\App\Http\Controllers\Api\ServiceController::class, 'uploadPortfolio']);
        Route::delete('/seller/services/{serviceId}/images/{imageId}', [\App\Http\Controllers\Api\ServiceController::class, 'deleteImage']);

        // Favorites
        Route::post('/favorites/toggle', [\App\Http\Controllers\Api\FavoriteController::class, 'toggle']);
        Route::get('/favorites', [\App\Http\Controllers\Api\FavoriteController::class, 'index']);
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

        // Admin Category Management
        Route::post('/admin/product-categories', [\App\Http\Controllers\Api\ProductCategoryController::class, 'store']);
        Route::put('/admin/product-categories/{id}', [\App\Http\Controllers\Api\ProductCategoryController::class, 'update']);
        Route::delete('/admin/product-categories/{id}', [\App\Http\Controllers\Api\ProductCategoryController::class, 'destroy']);

        Route::post('/admin/service-categories', [\App\Http\Controllers\Api\ServiceCategoryController::class, 'store']);
        Route::put('/admin/service-categories/{id}', [\App\Http\Controllers\Api\ServiceCategoryController::class, 'update']);
        Route::delete('/admin/service-categories/{id}', [\App\Http\Controllers\Api\ServiceCategoryController::class, 'destroy']);
    });
});
