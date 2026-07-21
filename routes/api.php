<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\OrganizationController;
use App\Http\Controllers\Admin\RoomController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\BookingController as AdminBookingController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\DashboardController;

Route::middleware(['auth:sanctum'])->group(function () {
    // Guest/User REST API Routes
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::put('/bookings/{book}', [BookingController::class, 'update']);
    Route::delete('/bookings/{book}', [BookingController::class, 'destroy']);
    Route::get('/bookings/{book}', [BookingController::class, 'show']);

    // Admin REST API Routes
    Route::middleware(['role:admin'])->prefix('admin')->group(function () {
        // Dashboard Stats
        Route::get('/dashboard/stats', [DashboardController::class, 'stats']);

        // Organizations API
        Route::get('/organizations', [OrganizationController::class, 'index']);
        Route::post('/organizations', [OrganizationController::class, 'store']);
        Route::put('/organizations/{organization}', [OrganizationController::class, 'update']);
        Route::delete('/organizations/{organization}', [OrganizationController::class, 'destroy']);

        // Rooms API
        Route::get('/rooms', [RoomController::class, 'index']);
        Route::post('/rooms', [RoomController::class, 'store']);
        Route::put('/rooms/{room}', [RoomController::class, 'update']);
        Route::delete('/rooms/{room}', [RoomController::class, 'destroy']);

        // Users API
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::put('/users/{user}', [UserController::class, 'update']);
        Route::delete('/users/{user}', [UserController::class, 'destroy']);
        Route::patch('/users/{user}/reset-password', [UserController::class, 'resetPassword']);
        Route::get('/users/{user}/bookings', [UserController::class, 'bookings']);

        // Bookings API
        Route::get('/bookings', [AdminBookingController::class, 'index']);
        Route::post('/bookings', [AdminBookingController::class, 'store']);
        Route::put('/bookings/{book}', [AdminBookingController::class, 'update']);
        Route::post('/bookings/{book}/approve', [AdminBookingController::class, 'approve']);
        Route::post('/bookings/{book}/reject', [AdminBookingController::class, 'reject']);
        Route::post('/bookings/bulk-approve', [AdminBookingController::class, 'bulkApprove']);
        Route::post('/bookings/bulk-reject', [AdminBookingController::class, 'bulkReject']);
        Route::get('/bookings/{book}', [AdminBookingController::class, 'show']);
    });
});
