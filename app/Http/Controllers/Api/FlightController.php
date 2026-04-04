<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Flight;
use App\Services\FlightService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FlightController extends Controller
{
    public function __construct(
        private readonly FlightService $flightService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['departure', 'arrival', 'airline']);

        return response()->json($this->flightService->search($filters));
    }

    public function show(Flight $flight): JsonResponse
    {
        return response()->json($this->flightService->getWithRelations($flight));
    }
}
