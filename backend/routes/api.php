<?php

use App\Http\Controllers\Api\AdminRoleController;
use App\Http\Controllers\Api\AdminStoreController;
use App\Http\Controllers\Api\AlumniVerificationController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductCategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\SellerOrderController;
use App\Http\Controllers\Api\ServiceCategoryController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\StoreController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::middleware('throttle:login')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});
Route::get('/stores/{id}', [StoreController::class, 'show'])->where('id', '(?!my-store)[^/]+');
Route::get('/product-categories', [ProductCategoryController::class, 'index']);
Route::get('/service-categories', [ServiceCategoryController::class, 'index']);
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/id/{id}', [ProductController::class, 'showById']);
Route::get('/products/{slug}', [ProductController::class, 'show']);
Route::get('/services', [ServiceController::class, 'index']);
Route::get('/services/{slug}', [ServiceController::class, 'show']);
Route::get('/catalog', [CatalogController::class, 'index']);
Route::get('/catalog/locations', [CatalogController::class, 'locations']);
Route::get('/reviews', [ReviewController::class, 'index']);

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
        Route::post('/stores', [StoreController::class, 'register']);
        Route::get('/stores/my-store', [StoreController::class, 'myStore']);
        Route::put('/stores/my-store', [StoreController::class, 'updateMyStore']);
        Route::post('/stores/my-store/logo', [StoreController::class, 'uploadLogo']);
        Route::post('/stores/my-store/banner', [StoreController::class, 'uploadBanner']);

        // Seller Products
        Route::get('/seller/products', [ProductController::class, 'sellerProducts']);
        Route::post('/seller/products', [ProductController::class, 'store']);
        Route::get('/seller/products/{id}', [ProductController::class, 'sellerShow']);
        Route::put('/seller/products/{id}', [ProductController::class, 'update']);
        Route::delete('/seller/products/{id}', [ProductController::class, 'destroy']);
        Route::post('/seller/products/{id}/image', [ProductController::class, 'uploadImage']);
        Route::post('/seller/products/{id}/gallery', [ProductController::class, 'uploadGallery']);
        Route::delete('/seller/products/{productId}/images/{imageId}', [ProductController::class, 'deleteImage']);

        // Seller Services
        Route::get('/seller/services', [ServiceController::class, 'sellerServices']);
        Route::post('/seller/services', [ServiceController::class, 'store']);
        Route::get('/seller/services/{id}', [ServiceController::class, 'sellerShow']);
        Route::put('/seller/services/{id}', [ServiceController::class, 'update']);
        Route::delete('/seller/services/{id}', [ServiceController::class, 'destroy']);
        Route::post('/seller/services/{id}/image', [ServiceController::class, 'uploadImage']);
        Route::post('/seller/services/{id}/portfolio', [ServiceController::class, 'uploadPortfolio']);
        Route::delete('/seller/services/{serviceId}/images/{imageId}', [ServiceController::class, 'deleteImage']);

        // Favorites
        Route::post('/favorites/toggle', [FavoriteController::class, 'toggle']);
        Route::get('/favorites', [FavoriteController::class, 'index']);

        // Cart
        Route::get('/cart', [CartController::class, 'index']);
        Route::post('/cart/items', [CartController::class, 'addItem']);
        Route::put('/cart/items/{id}', [CartController::class, 'updateItem']);
        Route::delete('/cart/items/{id}', [CartController::class, 'deleteItem']);
        Route::delete('/cart', [CartController::class, 'clear']);

        // Checkout
        Route::post('/checkout', [CheckoutController::class, 'checkout']);

        // Buyer Orders
        Route::get('/orders', [OrderController::class, 'index']);
        Route::get('/orders/{id}', [OrderController::class, 'show']);
        Route::post('/orders/{id}/cancel', [OrderController::class, 'cancel']);

        // Seller Orders
        Route::get('/seller/orders', [SellerOrderController::class, 'index']);
        Route::get('/seller/orders/stats', [SellerOrderController::class, 'stats']);
        Route::get('/seller/orders/{id}', [SellerOrderController::class, 'show']);
        Route::put('/seller/orders/{id}/status', [SellerOrderController::class, 'updateStatus']);

        // Reviews
        Route::post('/reviews', [ReviewController::class, 'store']);
        Route::post('/reviews/{id}/reply', [ReviewController::class, 'reply']);

        // Notifications
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
        Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);

        // Dashboard
        Route::get('/dashboard/seller', [DashboardController::class, 'seller']);
        Route::get('/dashboard/buyer', [DashboardController::class, 'buyer']);
    });

    // Admin routes
    Route::middleware('admin')->group(function () {
        Route::get('/dashboard/admin', [DashboardController::class, 'admin']);

        // Reports & Exports
        Route::get('/admin/reports/alumni/export', [ReportController::class, 'exportAlumni']);
        Route::get('/admin/reports/stores/export', [ReportController::class, 'exportStores']);
        Route::get('/admin/reports/products/export', [ReportController::class, 'exportProducts']);
        Route::get('/admin/reports/services/export', [ReportController::class, 'exportServices']);
        Route::get('/admin/reports/orders/export', [ReportController::class, 'exportOrders']);
        Route::get('/admin/reports/sales/export', [ReportController::class, 'exportSales']);

        // Roles & Permissions CRUD
        Route::get('/admin/roles', [AdminRoleController::class, 'getRoles']);
        Route::post('/admin/roles', [AdminRoleController::class, 'createRole']);
        Route::put('/admin/roles/{id}', [AdminRoleController::class, 'updateRole']);
        Route::delete('/admin/roles/{id}', [AdminRoleController::class, 'deleteRole']);
        Route::get('/admin/permissions', [AdminRoleController::class, 'getPermissions']);
        Route::post('/admin/users/{id}/assign-role', [AdminRoleController::class, 'assignRole']);

        // Alumni Verification CRUD & Excel Import
        Route::post('/admin/alumni/import', [AlumniVerificationController::class, 'import'])->middleware('throttle:uploads');
        Route::get('/admin/alumni', [AlumniVerificationController::class, 'index']);
        Route::get('/admin/alumni/{id}', [AlumniVerificationController::class, 'show']);
        Route::post('/admin/alumni/{id}/verify', [AlumniVerificationController::class, 'verify']);

        // Admin Store Management
        Route::get('/admin/stores', [AdminStoreController::class, 'index']);
        Route::post('/admin/stores/{id}/verify', [AdminStoreController::class, 'verify']);

        // Admin Product Moderation
        Route::get('/admin/stores/{store}/products', [ProductController::class, 'adminStoreProducts']);
        Route::delete('/admin/products/{product}', [ProductController::class, 'adminDestroy']);
        Route::patch('/admin/products/{product}/status', [ProductController::class, 'adminToggleStatus']);

        // Admin Service Moderation
        Route::get('/admin/stores/{store}/services', [ServiceController::class, 'adminStoreServices']);
        Route::delete('/admin/services/{service}', [ServiceController::class, 'adminDestroy']);
        Route::patch('/admin/services/{service}/status', [ServiceController::class, 'adminToggleStatus']);

        // Admin Finance
        Route::get('/admin/finance/summary', [App\Http\Controllers\Api\AdminFinanceController::class, 'summary']);
        Route::get('/admin/finance/per-store', [App\Http\Controllers\Api\AdminFinanceController::class, 'perStore']);
        Route::get('/admin/finance/store/{storeId}', [App\Http\Controllers\Api\AdminFinanceController::class, 'storeDetail']);

        // Admin Category Management
        Route::post('/admin/product-categories', [ProductCategoryController::class, 'store']);
        Route::put('/admin/product-categories/{id}', [ProductCategoryController::class, 'update']);
        Route::delete('/admin/product-categories/{id}', [ProductCategoryController::class, 'destroy']);

        Route::post('/admin/service-categories', [ServiceCategoryController::class, 'store']);
        Route::put('/admin/service-categories/{id}', [ServiceCategoryController::class, 'update']);
        Route::delete('/admin/service-categories/{id}', [ServiceCategoryController::class, 'destroy']);
    });
});
