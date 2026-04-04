<?php

use App\Http\Controllers\Admin\AdminAirlineController;
use App\Http\Controllers\Admin\AdminAirportController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminFlightController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
    Route::inertia('flights', 'Flights/Index')->name('flights.index');
    Route::inertia('trips', 'Trips/Index')->name('trips.index');
    Route::inertia('trips/new', 'Trips/Create')->name('trips.create');
});

// ─── Admin ────────────────────────────────────────────────────────────────────
Route::middleware(['auth', 'verified', EnsureUserIsAdmin::class])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::resource('users', AdminUserController::class);
        Route::resource('airlines', AdminAirlineController::class);
        Route::resource('airports', AdminAirportController::class);
        Route::resource('flights', AdminFlightController::class);
    });

require __DIR__.'/settings.php';
