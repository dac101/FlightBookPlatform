<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAirlineRequest;
use App\Http\Requests\Admin\UpdateAirlineRequest;
use App\Services\AirlineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminAirlineController extends Controller
{
    public function __construct(
        private readonly AirlineService $airlineService
    ) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json(
            $this->airlineService->list(
                (int) $request->integer('per_page', 10),
                $request->only(['search', 'sort'])
            )
        );
    }

    public function options(): JsonResponse
    {
        return response()->json($this->airlineService->all());
    }

    public function show(int $airline): JsonResponse
    {
        return response()->json($this->airlineService->find($airline));
    }

    public function store(StoreAirlineRequest $request): JsonResponse
    {
        $airline = $this->airlineService->create($request->validated());

        return response()->json([
            'message' => 'Airline created successfully.',
            'data' => $airline,
        ], 201);
    }

    public function update(UpdateAirlineRequest $request, int $airline): JsonResponse
    {
        $updatedAirline = $this->airlineService->update($airline, $request->validated());

        return response()->json([
            'message' => 'Airline updated successfully.',
            'data' => $updatedAirline,
        ]);
    }

    public function destroy(int $airline): JsonResponse
    {
        $this->airlineService->delete($airline);

        return response()->json([
            'message' => 'Airline deleted successfully.',
        ]);
    }
}
