<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Airline;
use App\Services\AirlineService;
use Illuminate\Http\JsonResponse;

class AirlineController extends Controller
{
    public function __construct(
        private readonly AirlineService $airlineService
    ) {}

    public function index(): JsonResponse
    {
        return response()->json($this->airlineService->list());
    }

    public function show(Airline $airline): JsonResponse
    {
        return response()->json($this->airlineService->getWithFlights($airline));
    }
}
