<?php

namespace App\Repositories;

use App\Models\Airline;
use App\Models\Airport;
use App\Models\Flight;
use App\Repositories\Contracts\FlightImportRepositoryInterface;
use Illuminate\Support\Facades\Log;
use Throwable;

class FlightImportRepository implements FlightImportRepositoryInterface
{
    public function findAirportByIata(string $iataCode): ?Airport
    {
        try {
            $airport = Airport::where('iata_code', strtoupper($iataCode))->first();

            Log::channel('flights')->debug('FlightImportRepository: airport lookup', [
                'iata_code' => $iataCode,
                'found' => $airport !== null,
            ]);

            return $airport;
        } catch (Throwable $e) {
            Log::channel('flights')->error('FlightImportRepository: error looking up airport', [
                'iata_code' => $iataCode,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    /**
     * @param  array{iata_code: string, name: string, city: string, city_code: string, country_code: string, region_code: string|null, latitude: float, longitude: float, timezone: string}  $data
     */
    public function createAirport(array $data): Airport
    {
        try {
            $airport = Airport::create($data);

            Log::channel('flights')->info('FlightImportRepository: airport created from CSV', [
                'iata_code' => $data['iata_code'],
                'name' => $data['name'],
                'city' => $data['city'],
            ]);

            return $airport;
        } catch (Throwable $e) {
            Log::channel('flights')->error('FlightImportRepository: error creating airport', [
                'iata_code' => $data['iata_code'] ?? 'unknown',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    public function findOrCreateAirline(string $iataCode, string $name): Airline
    {
        try {
            [$airline, $created] = [
                Airline::firstOrCreate(
                    ['iata_code' => strtoupper($iataCode)],
                    ['name' => $name ?: $iataCode]
                ),
                false,
            ];

            // Re-check wasRecentlyCreated after firstOrCreate
            $created = $airline->wasRecentlyCreated;

            Log::channel('flights')->info('FlightImportRepository: airline resolved', [
                'iata_code' => $iataCode,
                'created' => $created,
            ]);

            return $airline;
        } catch (Throwable $e) {
            Log::channel('flights')->error('FlightImportRepository: error resolving airline', [
                'iata_code' => $iataCode,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    public function flightExists(int $airlineId, string $flightNumber): bool
    {
        try {
            $exists = Flight::where('airline_id', $airlineId)
                ->where('flight_number', $flightNumber)
                ->exists();

            Log::channel('flights')->debug('FlightImportRepository: duplicate check', [
                'airline_id' => $airlineId,
                'flight_number' => $flightNumber,
                'exists' => $exists,
            ]);

            return $exists;
        } catch (Throwable $e) {
            Log::channel('flights')->error('FlightImportRepository: error checking flight duplicate', [
                'airline_id' => $airlineId,
                'flight_number' => $flightNumber,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Treat as exists to avoid duplicate inserts on uncertainty
            return true;
        }
    }

    /**
     * @param  array{airline_id: int, flight_number: string, airport_departure_id: int, airport_arrival_id: int, departure_time: string, arrival_time: string, price: float}  $data
     */
    public function createFlight(array $data): Flight
    {
        try {
            $flight = Flight::create($data);

            Log::channel('flights')->info('FlightImportRepository: flight created', [
                'flight_number' => $data['flight_number'],
                'airline_id' => $data['airline_id'],
            ]);

            return $flight;
        } catch (Throwable $e) {
            Log::channel('flights')->error('FlightImportRepository: error creating flight', [
                'flight_number' => $data['flight_number'] ?? 'unknown',
                'airline_id' => $data['airline_id'] ?? 'unknown',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
