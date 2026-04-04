<?php

use App\Http\Controllers\Admin\AdminAirlineController;
use App\Http\Controllers\Admin\AdminAirportController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminFlightController;
use App\Http\Controllers\Admin\AdminTripController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Api\ClientTripBuilderController;
use App\Http\Controllers\Api\TripController as UserTripController;
use App\Http\Controllers\ProfileController;
use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/trips', fn () => Inertia::render('Trips/Index'))->name('trips.page');
    Route::get('/settings', fn () => Inertia::render('Settings/Index'))->name('settings.index');

    Route::prefix('client-api')->name('client-api.')->group(function (): void {
        Route::get('/trips', [UserTripController::class, 'index'])->name('trips.index');
        Route::get('/airports/search', [ClientTripBuilderController::class, 'airportSuggestions'])->name('airports.search');
        Route::get('/airlines/options', [ClientTripBuilderController::class, 'airlineOptions'])->name('airlines.options');
        Route::post('/trip-builder/flights/search', [ClientTripBuilderController::class, 'searchFlights'])->name('trip-builder.search');
        Route::post('/trip-builder/book', [ClientTripBuilderController::class, 'book'])->name('trip-builder.book');
    });
});

Route::middleware(['auth', 'verified', EnsureUserIsAdmin::class])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

        Route::prefix('api')
            ->name('api.')
            ->group(function (): void {
                Route::get('/dashboard/stats', [AdminDashboardController::class, 'stats'])->name('dashboard.stats');

                Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
                Route::get('/users/{user}', [AdminUserController::class, 'show'])->name('users.show');
                Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
                Route::patch('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
                Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');

                Route::get('/airlines/options', [AdminAirlineController::class, 'options'])->name('airlines.options');
                Route::get('/airlines', [AdminAirlineController::class, 'index'])->name('airlines.index');
                Route::get('/airlines/{airline}', [AdminAirlineController::class, 'show'])->name('airlines.show');
                Route::post('/airlines', [AdminAirlineController::class, 'store'])->name('airlines.store');
                Route::patch('/airlines/{airline}', [AdminAirlineController::class, 'update'])->name('airlines.update');
                Route::delete('/airlines/{airline}', [AdminAirlineController::class, 'destroy'])->name('airlines.destroy');

                Route::get('/airports/options', [AdminAirportController::class, 'options'])->name('airports.options');
                Route::get('/airports', [AdminAirportController::class, 'index'])->name('airports.index');
                Route::get('/airports/{airport}', [AdminAirportController::class, 'show'])->name('airports.show');
                Route::post('/airports', [AdminAirportController::class, 'store'])->name('airports.store');
                Route::patch('/airports/{airport}', [AdminAirportController::class, 'update'])->name('airports.update');
                Route::delete('/airports/{airport}', [AdminAirportController::class, 'destroy'])->name('airports.destroy');

                Route::get('/flights', [AdminFlightController::class, 'index'])->name('flights.index');
                Route::get('/flights/{flight}', [AdminFlightController::class, 'show'])->name('flights.show');
                Route::post('/flights', [AdminFlightController::class, 'store'])->name('flights.store');
                Route::patch('/flights/{flight}', [AdminFlightController::class, 'update'])->name('flights.update');
                Route::delete('/flights/{flight}', [AdminFlightController::class, 'destroy'])->name('flights.destroy');

                Route::get('/trips', [AdminTripController::class, 'index'])->name('trips.index');
                Route::get('/trips/{trip}', [AdminTripController::class, 'show'])->name('trips.show');
                Route::patch('/trips/{trip}', [AdminTripController::class, 'update'])->name('trips.update');
                Route::delete('/trips/{trip}', [AdminTripController::class, 'destroy'])->name('trips.destroy');
            });
    });

require __DIR__.'/auth.php';
