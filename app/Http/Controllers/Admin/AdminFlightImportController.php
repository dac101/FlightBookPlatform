<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessAviationStackFlightsJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminFlightImportController extends Controller
{
    /**
     * Accept a JSON file upload for a given airport IATA code,
     * save it to the aviation_stack storage folder, and dispatch
     * a processing job to the flight-imports Horizon queue.
     */
    public function upload(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'iata_code' => ['required', 'string', 'size:3', 'regex:/^[A-Za-z]{3}$/'],
            'file' => ['required', 'file', 'mimes:json', 'max:10240'],
        ]);

        $iataCode = strtoupper($validated['iata_code']);
        $timestamp = now()->format('Y-m-d_H-i-s');
        $relativePath = "aviation_stack/{$iataCode}/{$timestamp}.json";

        Storage::put($relativePath, file_get_contents($request->file('file')->getRealPath()));

        ProcessAviationStackFlightsJob::dispatch($relativePath, $iataCode)
            ->onQueue('flight-imports');

        return response()->json([
            'message' => "File saved and job dispatched to Horizon for {$iataCode}.",
            'path' => $relativePath,
        ]);
    }
}
