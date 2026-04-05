<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Services\TripBuilderService;
use App\Services\TripService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TripController extends Controller
{
    public function __construct(
        private readonly TripService $tripService,
        private readonly TripBuilderService $tripBuilderService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json(
            $this->tripService->listForUser(
                $request->user(),
                (int) $request->integer('per_page', 10)
            )
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'trip_name' => 'nullable|string|max:120',
            'trip_type' => 'required|string|in:one_way,round_trip,open_jaw,multi_city',
            'departure_date' => 'required|date|after_or_equal:today|before_or_equal:'.now()->addDays(365)->toDateString(),
        ]);

        $trip = $this->tripService->create($request->user(), $validated);

        return response()->json($trip, Response::HTTP_CREATED);
    }

    public function show(Request $request, Trip $trip): JsonResponse
    {
        $this->authorize('view', $trip);

        return response()->json($this->tripService->getWithSegments($trip));
    }

    public function update(Request $request, Trip $trip): JsonResponse
    {
        $this->authorize('update', $trip);

        $validated = $request->validate([
            'trip_name' => 'sometimes|nullable|string|max:120',
            'status' => 'sometimes|string|in:pending,confirmed,cancelled',
            'departure_date' => 'sometimes|date|after_or_equal:today|before_or_equal:'.now()->addDays(365)->toDateString(),
        ]);

        return response()->json($this->tripService->update($trip, $validated));
    }

    public function destroy(Request $request, Trip $trip): JsonResponse
    {
        $this->authorize('delete', $trip);

        $this->tripService->delete($trip);

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    public function storeFromFlight(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'trip_name' => ['nullable', 'string', 'max:120'],
            'flight_id' => ['required', 'integer', 'exists:flights,id'],
            'departure_date' => ['required', 'date'],
        ]);

        $trip = $this->tripBuilderService->createTripFromFlight($request->user(), $validated);

        return response()->json([
            'message' => 'Trip created from selected flight.',
            'data' => $trip,
        ], Response::HTTP_CREATED);
    }

    public function appendFlight(Request $request, Trip $trip): JsonResponse
    {
        $this->authorize('update', $trip);

        $validated = $request->validate([
            'flight_id' => ['required', 'integer', 'exists:flights,id'],
            'departure_date' => ['required', 'date'],
        ]);

        $updatedTrip = $this->tripBuilderService->appendFlightToTrip($request->user(), $trip, $validated);

        return response()->json([
            'message' => 'Flight added to trip plan.',
            'data' => $updatedTrip,
        ]);
    }
}
