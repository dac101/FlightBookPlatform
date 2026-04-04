<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreFlightRequest;
use App\Http\Requests\Admin\UpdateFlightRequest;
use App\Models\Flight;
use App\Services\AirlineService;
use App\Services\AirportService;
use App\Services\FlightService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AdminFlightController extends Controller
{
    public function __construct(
        private readonly FlightService $flightService,
        private readonly AirlineService $airlineService,
        private readonly AirportService $airportService,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Flights/Index', [
            'flights' => $this->flightService->search([]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Flights/Create', [
            'airlines' => $this->airlineService->all(),
            'airports' => $this->airportService->all(),
        ]);
    }

    public function store(StoreFlightRequest $request): RedirectResponse
    {
        Flight::create($request->validated());

        return redirect()->route('admin.flights.index')->with('success', 'Flight created successfully.');
    }

    public function edit(Flight $flight): Response
    {
        return Inertia::render('Admin/Flights/Edit', [
            'flight' => $flight->load(['airline', 'departureAirport', 'arrivalAirport']),
            'airlines' => $this->airlineService->all(),
            'airports' => $this->airportService->all(),
        ]);
    }

    public function update(UpdateFlightRequest $request, Flight $flight): RedirectResponse
    {
        $flight->update($request->validated());

        return redirect()->route('admin.flights.index')->with('success', 'Flight updated successfully.');
    }

    public function destroy(Flight $flight): RedirectResponse
    {
        $flight->delete();

        return redirect()->route('admin.flights.index')->with('success', 'Flight deleted successfully.');
    }
}
