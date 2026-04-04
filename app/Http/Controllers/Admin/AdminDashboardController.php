<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AirlineService;
use App\Services\AirportService;
use App\Services\FlightService;
use App\Services\TripService;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    public function __construct(
        private readonly UserService $userService,
        private readonly AirlineService $airlineService,
        private readonly AirportService $airportService,
        private readonly FlightService $flightService,
        private readonly TripService $tripService,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Dashboard');
    }

    public function stats(): JsonResponse
    {
        return response()->json([
            'users' => $this->userService->count(),
            'airlines' => $this->airlineService->count(),
            'airports' => $this->airportService->count(),
            'flights' => $this->flightService->count(),
            'trips' => $this->tripService->count(),
        ]);
    }
}
