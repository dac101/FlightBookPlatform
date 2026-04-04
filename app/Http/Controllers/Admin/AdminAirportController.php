<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAirportRequest;
use App\Http\Requests\Admin\UpdateAirportRequest;
use App\Services\AirportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminAirportController extends Controller
{
    public function __construct(
        private readonly AirportService $airportService
    ) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json(
            $this->airportService->list(
                (int) $request->integer('per_page', 10),
                $request->only(['search', 'sort'])
            )
        );
    }

    public function options(): JsonResponse
    {
        return response()->json($this->airportService->all());
    }

    public function show(int $airport): JsonResponse
    {
        return response()->json($this->airportService->find($airport));
    }

    public function store(StoreAirportRequest $request): JsonResponse
    {
        $airport = $this->airportService->create($request->validated());

        return response()->json([
            'message' => 'Airport created successfully.',
            'data' => $airport,
        ], 201);
    }

    public function update(UpdateAirportRequest $request, int $airport): JsonResponse
    {
        $updatedAirport = $this->airportService->update($airport, $request->validated());

        return response()->json([
            'message' => 'Airport updated successfully.',
            'data' => $updatedAirport,
        ]);
    }

    public function destroy(int $airport): JsonResponse
    {
        $this->airportService->delete($airport);

        return response()->json([
            'message' => 'Airport deleted successfully.',
        ]);
    }
}
