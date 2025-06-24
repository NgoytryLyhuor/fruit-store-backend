<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FruitController;
use App\Http\Controllers\OrderController;

// Public routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

Route::get('/fruits', [FruitController::class, 'index']);
Route::get('/fruits/categories', [FruitController::class, 'categories']);
Route::get('/fruits/{fruit}', [FruitController::class, 'show']);

// Protected routes
Route::middleware(['jwt.auth'])->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/profile', [AuthController::class, 'profile']);
    Route::get('/user', [AuthController::class, 'users']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/update', [AuthController::class, 'update']);

    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::put('/orders/{order}', [OrderController::class, 'update']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);

    // Admin routes (add middleware for admin check)
    Route::post('/fruits', [FruitController::class, 'store']);
    Route::put('/fruits/{fruit}', [FruitController::class, 'update']);
    Route::delete('/fruits/{fruit}', [FruitController::class, 'destroy']);
});
