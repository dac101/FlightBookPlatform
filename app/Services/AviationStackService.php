<?php

namespace App\Services;

use App\Jobs\ProcessAviationStackFlightsJob;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class AviationStackService
{
    private const BASE_URL = 'https://api.aviationstack.com/v1';

    public function __construct(
        private readonly string $accessKey = ''
    ) {}

    /**
     * Fetch live arrivals for a given airport IATA code.
     * Returns null on failure so callers can decide how to handle it.
     */
    public function fetchArrivals(string $iataCode): ?array
    {
        try {
            $response = Http::timeout(30)
                ->get(self::BASE_URL.'/flights', [
                    'access_key' => $this->accessKey ?: config('services.aviationstack.key'),
                    'arr_iata' => strtoupper($iataCode),
                ]);

            $response->throw();

            Log::channel('flights')->info('AviationStackService: arrivals fetched', [
                'airport' => $iataCode,
                'count' => count($response->json('data') ?? []),
            ]);

            return $response->json();
        } catch (ConnectionException $e) {
            Log::channel('flights')->error('AviationStackService: connection failed', [
                'airport' => $iataCode,
                'message' => $e->getMessage(),
            ]);
        } catch (RequestException $e) {
            Log::channel('flights')->error('AviationStackService: HTTP request failed', [
                'airport' => $iataCode,
                'status' => $e->response->status(),
                'body' => $e->response->body(),
            ]);
        } catch (Throwable $e) {
            Log::channel('flights')->error('AviationStackService: unexpected error during fetch', [
                'airport' => $iataCode,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return null;
    }

    /**
     * Save raw API response to storage.
     * On success, dispatches ProcessAviationStackFlightsJob to the flight-imports queue.
     * Returns the relative storage path, or null on failure.
     */
    public function saveToJson(string $iataCode, array $data): ?string
    {
        try {
            $relativePath = sprintf(
                'aviation_stack/%s/%s.json',
                strtoupper($iataCode),
                now()->format('Y-m-d_H-i-s')
            );

            Storage::put($relativePath, json_encode($data, JSON_PRETTY_PRINT));

            Log::channel('flights')->info('AviationStackService: flight data saved to JSON', [
                'airport' => $iataCode,
                'flights' => count($data['data'] ?? []),
                'path' => $relativePath,
            ]);

            ProcessAviationStackFlightsJob::dispatch($relativePath, $iataCode)
                ->onQueue('flight-imports');

            Log::channel('flights')->info('AviationStackService: job dispatched to Horizon', [
                'airport' => $iataCode,
                'queue' => 'flight-imports',
                'path' => $relativePath,
            ]);

            return $relativePath;
        } catch (Throwable $e) {
            Log::channel('flights')->error('AviationStackService: failed to save JSON or dispatch job', [
                'airport' => $iataCode,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return null;
    }
}
