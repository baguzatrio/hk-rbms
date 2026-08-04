<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\BookingController;
use App\Http\Controllers\Api\V1\RoomDisplayController;
use App\Http\Controllers\Api\V1\Admin\BookingController as AdminBookingController;
use App\Http\Controllers\Api\V1\Admin\RoomController as AdminRoomController;
use App\Http\Controllers\Api\V1\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\V1\Admin\OrganizationController as AdminOrganizationController;
use App\Http\Controllers\Api\V1\Admin\ActivityLogController as AdminActivityLogController;

/*
|--------------------------------------------------------------------------
| API V1 Routes (HK-RBMS REST API)
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // Public TV Display Endpoint
    Route::get('/display/rooms/{room}', [RoomDisplayController::class, 'show']);

    // Authentication Endpoints
    Route::prefix('auth')->group(function () {
        Route::post('/login',  [AuthController::class, 'login']);
        Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
        Route::get('/me',      [AuthController::class, 'me'])->middleware('auth:sanctum');
    });

    // Authenticated Routes
    Route::middleware(['auth:sanctum'])->group(function () {

        // User Dashboard
        Route::get('/dashboard/user', [DashboardController::class, 'userDashboard']);

        // User Bookings API
        Route::apiResource('bookings', BookingController::class);

        // Admin Only Routes
        Route::middleware(['role:admin'])->prefix('admin')->group(function () {

            // Admin Dashboard
            Route::get('/dashboard/stats', [DashboardController::class, 'adminDashboard']);

            // Admin Booking Management & Bulk Actions
            Route::get('/bookings',                   [AdminBookingController::class, 'index']);
            Route::post('/bookings/{book}/approve',   [AdminBookingController::class, 'approve']);
            Route::post('/bookings/{book}/reject',    [AdminBookingController::class, 'reject']);
            Route::post('/bookings/bulk-approve',     [AdminBookingController::class, 'bulkApprove']);
            Route::post('/bookings/bulk-reject',      [AdminBookingController::class, 'bulkReject']);

            // Master Data API
            Route::apiResource('rooms',         AdminRoomController::class);
            Route::apiResource('users',         AdminUserController::class);
            Route::post('/users/{user}/reset-password', [AdminUserController::class, 'resetPassword']);
            Route::apiResource('organizations', AdminOrganizationController::class);

            // Audit Logs API
            Route::get('/activity-logs',        [AdminActivityLogController::class, 'index']);
            Route::get('/activity-logs/export', [AdminActivityLogController::class, 'export']);
        });
    });
});
