<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\ExpenseController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\TransactionController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\WarungSettingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    Route::get('/test', function () {
        return response()->json([
            'message' => 'Success',
        ]);
    });

    // Auth Routes
    Route::prefix('auth')->group(function () {
        // Public Auth
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);

        // Protected Auth
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::post('/refresh', [AuthController::class, 'refresh']);
            Route::put('/change-password', [AuthController::class, 'changePassword']);
        });
    });

    // User Profile Routes
    Route::prefix('users')->middleware('auth:sanctum')->group(function () {
        Route::get('/me', [UserController::class, 'me']);
        Route::put('/me', [UserController::class, 'updateProfile']);
    });

    // Export Route (allows token in query parameter)
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('/products/export', [ProductController::class, 'export']);
        Route::get('/products/export-menu', [ProductController::class, 'exportMenu']);
    });

    // Protected Routes
    Route::middleware('auth:sanctum')->group(function () {

        // Dashboard
        Route::middleware('role:OWNER,ADMIN_TOKO,ADMIN_KANTOR')->group(function () {
            Route::get('/dashboard', [DashboardController::class, 'index']);
        });

        // Reports
        Route::middleware('role:OWNER,ADMIN_TOKO,ADMIN_KANTOR')->group(function () {
            Route::get('/reports/sales', [ReportController::class, 'sales']);
            Route::get('/reports/profit-loss', [ReportController::class, 'profitLoss']);
        });

        // Settings
        Route::get('/settings/warung', [WarungSettingController::class, 'show']);
        Route::middleware('role:OWNER')->group(function () {
            Route::put('/settings/warung', [WarungSettingController::class, 'update']);
            Route::post('/settings/warung/logo', [WarungSettingController::class, 'uploadLogo']);
        });

        // Categories
        Route::middleware('role:OWNER,ADMIN_TOKO,ADMIN_KANTOR')->group(function () {
            Route::post('/categories/layout', [CategoryController::class, 'updateLayout']);
            Route::post('/categories', [CategoryController::class, 'store']);
            Route::put('/categories/{id}', [CategoryController::class, 'update']);
            Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);
        });
        Route::get('/categories', [CategoryController::class, 'index']);

        // Products
        Route::middleware('role:OWNER,ADMIN_TOKO,ADMIN_KANTOR')->group(function () {
            Route::post('/products/layout', [ProductController::class, 'updateLayout']);
            Route::post('/products', [ProductController::class, 'store']);
            Route::put('/products/{id}', [ProductController::class, 'update']);
            Route::delete('/products/{id}', [ProductController::class, 'destroy']);
            Route::post('/products/{id}/image', [ProductController::class, 'uploadImage']);
            Route::patch('/products/{id}/stock', [ProductController::class, 'updateStock']);
        });
        Route::get('/products', [ProductController::class, 'index']);
        Route::get('/products/{id}', [ProductController::class, 'show']);

        // Transactions
        Route::middleware('role:OWNER,ADMIN_TOKO,ADMIN_KANTOR')->group(function () {
            Route::get('/transactions', [TransactionController::class, 'index']);
            Route::post('/transactions', [TransactionController::class, 'store']);
            Route::get('/transactions/{id}', [TransactionController::class, 'show']);
            Route::patch('/transactions/{id}/status', [TransactionController::class, 'updateStatus']);
            Route::post('/transactions/{id}/items', [TransactionController::class, 'addItems']);
            Route::patch('/transactions/{id}/items/{itemId}', [TransactionController::class, 'updateItem']);
            Route::patch('/transactions/{id}/items/{itemId}/served', [TransactionController::class, 'updateServedQty']);
            Route::delete('/transactions/{id}/items/{itemId}', [TransactionController::class, 'removeItem']);
        });

        // Transaction Cancellation (OWNER only)
        Route::middleware('role:ADMIN_TOKO')->group(function () {
            Route::patch('/transactions/{id}/cancel', [TransactionController::class, 'cancel']);
            Route::delete('/transactions/{id}', [TransactionController::class, 'destroy']);
        });

        // Expenses
        Route::middleware('role:OWNER,ADMIN_TOKO,ADMIN_KANTOR')->group(function () {
            Route::get('/expenses', [ExpenseController::class, 'index']);
            Route::post('/expenses', [ExpenseController::class, 'store']);
            Route::put('/expenses/{id}', [ExpenseController::class, 'update']);
            Route::delete('/expenses/{id}', [ExpenseController::class, 'destroy']);
        });

        // Users Management
        Route::middleware('role:OWNER,ADMIN_TOKO,ADMIN_KANTOR')->group(function () {
            Route::get('/users', [UserController::class, 'index']);
            Route::post('/users', [UserController::class, 'store']);
            Route::put('/users/{id}', [UserController::class, 'update']);
        });

        Route::middleware('role:OWNER')->group(function () {
            Route::delete('/users/{id}', [UserController::class, 'destroy']);
        });

    });

});
