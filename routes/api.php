<?php

use App\Http\Controllers\Api\AirlineController;
use App\Http\Controllers\Api\AirportController;
use App\Http\Controllers\Api\FlightController;
use App\Http\Controllers\Api\TripController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // ─── Airlines ────────────────────────────────────────────────────────────
    Route::apiResource('airlines', AirlineController::class)->only(['index', 'show']);

    // ─── Airports ────────────────────────────────────────────────────────────
    Route::apiResource('airports', AirportController::class)->only(['index', 'show']);

    // ─── Flights ─────────────────────────────────────────────────────────────
    Route::apiResource('flights', FlightController::class)->only(['index', 'show']);

    // ─── Trips (authenticated) ────────────────────────────────────────────────
    Route::middleware('auth:sanctum')->group(function () {
        Route::apiResource('trips', TripController::class);
    });
});
