<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Airport;
use App\Services\AirportService;
use Illuminate\Http\JsonResponse;

class AirportController extends Controller
{
    public function __construct(
        private readonly AirportService $airportService
    ) {}

    public function index(): JsonResponse
    {
        return response()->json($this->airportService->list());
    }

    public function show(Airport $airport): JsonResponse
    {
        return response()->json($this->airportService->getWithFlights($airport));
    }
}
