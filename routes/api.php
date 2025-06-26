<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FruitController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\NotificationController;

// Public routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Password reset routes (public)
Route::post('/auth/forgot-password', [AuthController::class, 'forgetPassword']);
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);
Route::post('/auth/resend-password-reset', [AuthController::class, 'resendPasswordReset']);

// Public fruit routes
Route::get('/fruits', [FruitController::class, 'index']);
Route::get('/fruits/categories', [FruitController::class, 'categories']);
Route::get('/fruits/{fruit}', [FruitController::class, 'show']);

// Protected routes
Route::middleware(['jwt.auth'])->group(function () {
    // Auth routes
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/profile', [AuthController::class, 'profile']);
    Route::get('/user', [AuthController::class, 'users']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/update', [AuthController::class, 'update']);

    // Notification routes
    Route::get('/notification-settings', [NotificationController::class, 'getSettings']);
    Route::put('/notification-settings', [NotificationController::class, 'updateSettings']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'getUnreadCount']);

    // Order routes
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::put('/orders/{order}', [OrderController::class, 'update']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);

    // Admin routes (consider adding admin middleware)
    Route::post('/fruits', [FruitController::class, 'store']);
    Route::put('/fruits/{fruit}', [FruitController::class, 'update']);
    Route::delete('/fruits/{fruit}', [FruitController::class, 'destroy']);
});
