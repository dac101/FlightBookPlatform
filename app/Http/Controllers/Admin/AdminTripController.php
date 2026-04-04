<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TripStatus;
use App\Enums\TripType;
use App\Http\Controllers\Controller;
use App\Services\TripService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminTripController extends Controller
{
    public function __construct(
        private readonly TripService $tripService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json(
            $this->tripService->listAll(
                (int) $request->integer('per_page', 10),
                $request->only(['search', 'status', 'trip_type', 'sort'])
            )
        );
    }

    public function show(int $trip): JsonResponse
    {
        return response()->json($this->tripService->getWithSegments($this->tripService->find($trip)));
    }

    public function update(Request $request, int $trip): JsonResponse
    {
        $validated = $request->validate([
            'trip_type' => ['sometimes', Rule::enum(TripType::class)],
            'status' => ['sometimes', Rule::enum(TripStatus::class)],
            'departure_date' => ['sometimes', 'date'],
        ]);

        $updatedTrip = $this->tripService->update($this->tripService->find($trip), $validated);

        return response()->json([
            'message' => 'Trip updated successfully.',
            'data' => $this->tripService->getWithSegments($updatedTrip),
        ]);
    }

    public function destroy(int $trip): JsonResponse
    {
        $this->tripService->delete($this->tripService->find($trip));

        return response()->json([
            'message' => 'Trip deleted successfully.',
        ]);
    }
}
