<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;

// Test route
Route::get('/test', function() {
    Log::info('Test route hit');
    return response()->json(['message' => 'API is working']);
});

Route::prefix('auth')->group(function () {
    // Public routes
    Route::post('/register', [AuthController::class, 'register'])
         ->name('auth.register');
    Route::post('/login', [AuthController::class, 'login'])
         ->name('auth.login');

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])
             ->name('auth.logout');
        Route::get('/user', [AuthController::class, 'user'])
             ->name('auth.user');
        Route::patch('/profile', [AuthController::class, 'updateProfile'])
             ->name('auth.profile.update');
    });
});
