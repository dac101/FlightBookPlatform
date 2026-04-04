<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAirlineRequest;
use App\Http\Requests\Admin\UpdateAirlineRequest;
use App\Models\Airline;
use App\Services\AirlineService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AdminAirlineController extends Controller
{
    public function __construct(
        private readonly AirlineService $airlineService
    ) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Airlines/Index', [
            'airlines' => $this->airlineService->list(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Airlines/Create');
    }

    public function store(StoreAirlineRequest $request): RedirectResponse
    {
        Airline::create($request->validated());

        return redirect()->route('admin.airlines.index')->with('success', 'Airline created successfully.');
    }

    public function edit(Airline $airline): Response
    {
        return Inertia::render('Admin/Airlines/Edit', [
            'airline' => $airline,
        ]);
    }

    public function update(UpdateAirlineRequest $request, Airline $airline): RedirectResponse
    {
        $airline->update($request->validated());

        return redirect()->route('admin.airlines.index')->with('success', 'Airline updated successfully.');
    }

    public function destroy(Airline $airline): RedirectResponse
    {
        $airline->delete();

        return redirect()->route('admin.airlines.index')->with('success', 'Airline deleted successfully.');
    }
}
