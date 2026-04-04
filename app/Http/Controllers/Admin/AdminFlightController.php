<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreFlightRequest;
use App\Http\Requests\Admin\UpdateFlightRequest;
use App\Services\FlightService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminFlightController extends Controller
{
    public function __construct(
        private readonly FlightService $flightService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json(
            $this->flightService->search(
                $request->only(['search', 'departure', 'arrival', 'airline', 'sort']),
                (int) $request->integer('per_page', 10)
            )
        );
    }

    public function show(int $flight): JsonResponse
    {
        return response()->json($this->flightService->find($flight)->load(['airline', 'departureAirport', 'arrivalAirport']));
    }

    public function store(StoreFlightRequest $request): JsonResponse
    {
        $flight = $this->flightService->create($request->validated());

        return response()->json([
            'message' => 'Flight created successfully.',
            'data' => $flight,
        ], 201);
    }

    public function update(UpdateFlightRequest $request, int $flight): JsonResponse
    {
        $updatedFlight = $this->flightService->update($flight, $request->validated());

        return response()->json([
            'message' => 'Flight updated successfully.',
            'data' => $updatedFlight,
        ]);
    }

    public function destroy(int $flight): JsonResponse
    {
        $this->flightService->delete($flight);

        return response()->json([
            'message' => 'Flight deleted successfully.',
        ]);
    }
}
