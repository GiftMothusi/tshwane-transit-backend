<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BusRouteController;
use App\Http\Controllers\BusScheduleController;
use App\Http\Controllers\BusRoutePlanningController;
use App\Http\Controllers\PaymentController;
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

// Bus System Routes
Route::prefix('v1')->group(function () {
    // Public transit information routes
    Route::get('/bus-schedules', [BusScheduleController::class, 'index'])
         ->name('schedules.index');
    Route::get('/bus-locations', [BusScheduleController::class, 'getLiveLocations'])
         ->name('schedules.locations');
    Route::get('/bus-stops/nearby', [BusRouteController::class, 'getNearbyStops'])
        ->name('stops.nearby');

    Route::post('/routes/plan', [BusRoutePlanningController::class, 'planRoute'])
    ->name('routes.plan');

    Route::get('/routes/search', [BusRouteController::class, 'search'])
         ->name('routes.search');
    Route::get('/schedules/search', [BusScheduleController::class, 'search'])
         ->name('schedules.search');

    Route::get('/wallet', [PaymentController::class, 'getWallet']);
    Route::post('/wallet/topup', [PaymentController::class, 'topupWallet']);
    Route::post('/tickets/purchase', [PaymentController::class, 'purchaseTicket']);
    Route::get('/tickets/active', [PaymentController::class, 'getActiveTickets']);



    // Protected transit management routes
    Route::middleware('auth:sanctum')->group(function () {
        // Bus Routes CRUD
        Route::prefix('routes')->group(function () {
            Route::get('/', [BusRouteController::class, 'index'])
                 ->name('routes.index');
            Route::post('/', [BusRouteController::class, 'store'])
                 ->name('routes.store');
            Route::get('/{id}', [BusRouteController::class, 'show'])
                 ->name('routes.show');
            Route::put('/{id}', [BusRouteController::class, 'update'])
                 ->name('routes.update');
            Route::delete('/{id}', [BusRouteController::class, 'destroy'])
                 ->name('routes.delete');

            // Route-specific schedules
            Route::get('/{id}/schedules', [BusRouteController::class, 'getRouteSchedules'])
                 ->name('routes.schedules');
            Route::post('/{id}/schedules', [BusRouteController::class, 'addRouteSchedule'])
                 ->name('routes.schedules.add');
        });

        // Schedule Management
        Route::prefix('schedules')->group(function () {
            Route::get('/{id}', [BusScheduleController::class, 'show'])
                 ->name('schedules.show');
            Route::post('/', [BusScheduleController::class, 'store'])
                 ->name('schedules.store');
            Route::put('/{id}', [BusScheduleController::class, 'update'])
                 ->name('schedules.update');
            Route::delete('/{id}', [BusScheduleController::class, 'destroy'])
                 ->name('schedules.delete');
        });
    });
});
