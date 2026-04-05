<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AirlineService;
use App\Services\FlightService;
use App\Services\TripBuilderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClientTripBuilderController extends Controller
{
    public function __construct(
        private readonly TripBuilderService $tripBuilderService,
        private readonly AirlineService $airlineService,
        private readonly FlightService $flightService,
    ) {}

    public function airportSuggestions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => ['required', 'string', 'min:1', 'max:255'],
        ]);

        return response()->json(
            $this->tripBuilderService->searchAirports($validated['query'])
        );
    }

    public function airlineOptions(): JsonResponse
    {
        return response()->json($this->airlineService->all());
    }

    public function searchFlights(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'departure_airport_id' => ['required', 'integer', 'exists:airports,id'],
            'arrival_airport_id' => ['required', 'integer', 'exists:airports,id', 'different:departure_airport_id'],
            'departure_date' => ['required', 'date', 'after_or_equal:today', 'before_or_equal:'.now()->addDays(365)->toDateString()],
            'preferred_airline_ids' => ['nullable', 'array'],
            'preferred_airline_ids.*' => ['integer', 'exists:airlines,id'],
            'radius_km' => ['nullable', 'numeric', 'min:1', 'max:1000'],
            'sort' => ['nullable', Rule::in(['price', 'departure_time', 'arrival_time', 'duration'])],
            'search' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        return response()->json(
            $this->tripBuilderService->searchFlights($validated)
        );
    }

    public function exploreFlights(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'departure' => ['nullable', 'string', 'max:255'],
            'arrival' => ['nullable', 'string', 'max:255'],
            'airline' => ['nullable', 'string', 'max:255'],
            'preferred_airline_ids' => ['nullable', 'array'],
            'preferred_airline_ids.*' => ['integer', 'exists:airlines,id'],
            'sort' => ['nullable', Rule::in(['recent', 'price', 'departure_time', 'arrival_time'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        return response()->json(
            $this->flightService->search($validated, (int) ($validated['per_page'] ?? 12))
        );
    }

    public function book(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'trip_name' => ['nullable', 'string', 'max:120'],
            'trip_type' => ['required', Rule::in(['one_way', 'round_trip', 'open_jaw', 'multi_city'])],
            'radius_km' => ['nullable', 'numeric', 'min:1', 'max:1000'],
            'legs' => ['required', 'array', 'min:1', 'max:5'],
            'legs.*.departure_airport_id' => ['required', 'integer', 'exists:airports,id'],
            'legs.*.arrival_airport_id' => ['required', 'integer', 'exists:airports,id'],
            'legs.*.departure_date' => ['required', 'date'],
            'legs.*.selected_flight_id' => ['required', 'integer', 'exists:flights,id'],
        ]);

        $trip = $this->tripBuilderService->book($request->user(), $validated);

        return response()->json([
            'message' => 'Trip booked successfully.',
            'data' => $trip,
        ], 201);
    }
}
