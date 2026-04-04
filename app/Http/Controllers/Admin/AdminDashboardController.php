<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AirlineService;
use App\Services\AirportService;
use App\Services\FlightService;
use App\Services\UserService;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    public function __construct(
        private readonly UserService $userService,
        private readonly AirlineService $airlineService,
        private readonly AirportService $airportService,
        private readonly FlightService $flightService,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'users' => $this->userService->count(),
                'airlines' => $this->airlineService->count(),
                'airports' => $this->airportService->count(),
                'flights' => $this->flightService->count(),
            ],
        ]);
    }
}
