<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAirportRequest;
use App\Http\Requests\Admin\UpdateAirportRequest;
use App\Models\Airport;
use App\Services\AirportService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AdminAirportController extends Controller
{
    public function __construct(
        private readonly AirportService $airportService
    ) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Airports/Index', [
            'airports' => $this->airportService->list(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Airports/Create');
    }

    public function store(StoreAirportRequest $request): RedirectResponse
    {
        Airport::create($request->validated());

        return redirect()->route('admin.airports.index')->with('success', 'Airport created successfully.');
    }

    public function edit(Airport $airport): Response
    {
        return Inertia::render('Admin/Airports/Edit', [
            'airport' => $airport,
        ]);
    }

    public function update(UpdateAirportRequest $request, Airport $airport): RedirectResponse
    {
        $airport->update($request->validated());

        return redirect()->route('admin.airports.index')->with('success', 'Airport updated successfully.');
    }

    public function destroy(Airport $airport): RedirectResponse
    {
        $airport->delete();

        return redirect()->route('admin.airports.index')->with('success', 'Airport deleted successfully.');
    }
}
