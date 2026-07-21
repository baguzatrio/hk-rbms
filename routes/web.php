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
        Route::get('/{book}/edit',   [BookingController::class, 'edit'])   ->name('edit');
    });

    // ── Admin routes ───────────────────────────────────────────
    Route::middleware(['role:admin'])->prefix('admin')->name('admin.')->group(function () {

        // Booking Management
        Route::prefix('bookings')->name('bookings.')->group(function () {
            Route::get('/',                          [Admin\BookingController::class, 'index'])        ->name('index');
            Route::get('/create',                    [Admin\BookingController::class, 'create'])       ->name('create');
            Route::get('/{book}/edit',               [Admin\BookingController::class, 'edit'])         ->name('edit');
            Route::get('/export',                    [Admin\BookingController::class, 'export'])       ->name('export');
        });

        // Room Management
        Route::resource('rooms',         Admin\RoomController::class)->only(['index', 'create', 'edit', 'show']);

        // User Management
        Route::resource('users',         Admin\UserController::class)->only(['index', 'create', 'edit']);

        // Organization Management
        Route::resource('organizations', Admin\OrganizationController::class)->only(['index', 'create', 'edit']);

        // Activity Log (read-only)
        Route::get('/logs',              [Admin\ActivityLogController::class, 'index'])->name('logs.index');
        Route::get('/logs/export',       [Admin\ActivityLogController::class, 'export'])->name('logs.export');

        // TV Display Selection (Admin)
        Route::get('/display',           [App\Http\Controllers\Admin\RoomDisplayController::class, 'index'])->name('display.index');
    });
});

// Public TV Display (Guest)
Route::get('/display/{room}',            [App\Http\Controllers\RoomDisplayController::class, 'show'])->name('display.show');

require __DIR__.'/settings.php';
