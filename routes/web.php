<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\Admin;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

// ── Authenticated routes ─────────────────────────────────────────
Route::middleware(['auth', 'verified'])->group(function () {

    // Dashboard (semua role)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Jadwal Rapat (semua role, read-only)
    Route::get('/schedule', [ScheduleController::class, 'index'])->name('schedule.index');
    Route::get('/schedule/{room}', [ScheduleController::class, 'show'])->name('schedule.show');

    // ── User: Booking Ruangan ──────────────────────────────────
    Route::prefix('bookings')->name('bookings.')->group(function () {
        Route::get('/',              [BookingController::class, 'index'])  ->name('index');
        Route::get('/create',        [BookingController::class, 'create']) ->name('create');
        Route::post('/',             [BookingController::class, 'store'])  ->name('store');
        Route::get('/{book}',        [BookingController::class, 'show'])   ->name('show');
        Route::delete('/{book}',     [BookingController::class, 'destroy'])->name('destroy');
    });

    // ── Admin routes ───────────────────────────────────────────
    Route::middleware(['role:admin'])->prefix('admin')->name('admin.')->group(function () {

        // Booking Management
        Route::prefix('bookings')->name('bookings.')->group(function () {
            Route::get('/',                          [Admin\BookingController::class, 'index'])        ->name('index');
            Route::get('/create',                    [Admin\BookingController::class, 'create'])       ->name('create');
            Route::post('/',                         [Admin\BookingController::class, 'store'])        ->name('store');
            Route::get('/{book}',                    [Admin\BookingController::class, 'show'])         ->name('show');
            Route::patch('/{book}/approve',          [Admin\BookingController::class, 'approve'])      ->name('approve');
            Route::patch('/{book}/reject',           [Admin\BookingController::class, 'reject'])       ->name('reject');
            Route::post('/bulk-approve',             [Admin\BookingController::class, 'bulkApprove'])  ->name('bulk-approve');
            Route::post('/bulk-reject',              [Admin\BookingController::class, 'bulkReject'])   ->name('bulk-reject');
            Route::get('/export',                    [Admin\BookingController::class, 'export'])       ->name('export');
        });

        // Room Management
        Route::resource('rooms',         Admin\RoomController::class);

        // User Management
        Route::resource('users',         Admin\UserController::class);
        Route::get('/users/{user}/bookings', [Admin\UserController::class, 'bookings'])
             ->name('users.bookings');
        Route::patch('/users/{user}/reset-password', [Admin\UserController::class, 'resetPassword'])
             ->name('users.reset-password');

        // Organization Management
        Route::resource('organizations', Admin\OrganizationController::class);

        // Activity Log (read-only)
        Route::get('/logs',              [Admin\ActivityLogController::class, 'index'])->name('logs.index');
        Route::get('/logs/export',       [Admin\ActivityLogController::class, 'export'])->name('logs.export');
    });
});

require __DIR__.'/settings.php';
