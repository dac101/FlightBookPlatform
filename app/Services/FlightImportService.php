<?php

namespace App\Services;

use App\Models\Airport;
use App\Repositories\Contracts\AirportCsvRepositoryInterface;
use App\Repositories\Contracts\FlightImportRepositoryInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class FlightImportService
{
    public function __construct(
        private readonly FlightImportRepositoryInterface $flightImportRepository,
        private readonly AirportCsvRepositoryInterface $airportCsvRepository,
    ) {}

    /**
     * Read a saved AviationStack JSON file from storage and process each flight record.
     *
     * @return array{processed: int, skipped: int, errors: int}
     */
    public function processStorageFile(string $storagePath, string $iataCode): array
    {
        $result = ['processed' => 0, 'skipped' => 0, 'errors' => 0];

        try {
            if (! Storage::exists($storagePath)) {
                Log::channel('flights')->warning('FlightImportService: storage file not found', [
                    'path' => $storagePath,
                    'airport' => $iataCode,
                ]);

                return $result;
            }

            $data = json_decode(Storage::get($storagePath), true);

            if (empty($data['data'])) {
                Log::channel('flights')->info('FlightImportService: no flight records in file', [
                    'path' => $storagePath,
                    'airport' => $iataCode,
                ]);

                return $result;
            }

            Log::channel('flights')->info('FlightImportService: starting file processing', [
                'path' => $storagePath,
                'airport' => $iataCode,
                'total' => count($data['data']),
            ]);

            foreach ($data['data'] as $record) {
                try {
                    $outcome = $this->processRecord($record);

                    if ($outcome === 'processed') {
                        $result['processed']++;
                    } elseif ($outcome === 'skipped') {
                        $result['skipped']++;
                    } else {
                        $result['errors']++;
                    }
                } catch (Throwable $e) {
                    $result['errors']++;
                    Log::channel('flights')->error('FlightImportService: unhandled error on record', [
                        'airport' => $iataCode,
                        'message' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }

            Log::channel('flights')->info('FlightImportService: file processing complete', [
                'airport' => $iataCode,
                'processed' => $result['processed'],
                'skipped' => $result['skipped'],
                'errors' => $result['errors'],
            ]);
        } catch (Throwable $e) {
            Log::channel('flights')->error('FlightImportService: failed to process storage file', [
                'path' => $storagePath,
                'airport' => $iataCode,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return $result;
    }

    /**
     * Process a single AviationStack flight record.
     * Returns 'processed', 'skipped', or 'error'.
     */
    private function processRecord(array $record): string
    {
        try {
            $airlineIata = $record['airline']['iata'] ?? null;
            $airlineName = $record['airline']['name'] ?? null;
            $flightNumber = $record['flight']['number'] ?? null;
            $depIata = $record['departure']['iata'] ?? null;
            $arrIata = $record['arrival']['iata'] ?? null;
            $depScheduled = $record['departure']['scheduled'] ?? null;
            $arrScheduled = $record['arrival']['scheduled'] ?? null;

            if (! $airlineIata || ! $flightNumber || ! $depIata || ! $arrIata || ! $depScheduled || ! $arrScheduled) {
                Log::channel('flights')->debug('FlightImportService: record missing required fields, skipping', [
                    'airline' => $airlineIata,
                    'flight' => $flightNumber,
                    'dep_iata' => $depIata,
                    'arr_iata' => $arrIata,
                ]);

                return 'skipped';
            }

            $departureAirport = $this->resolveAirport($depIata);
            $arrivalAirport = $this->resolveAirport($arrIata);

            if (! $departureAirport || ! $arrivalAirport) {
                Log::channel('flights')->warning('FlightImportService: could not resolve one or both airports, skipping record', [
                    'flight' => $flightNumber,
                    'dep_iata' => $depIata,
                    'arr_iata' => $arrIata,
                ]);

                return 'skipped';
            }

            $airline = $this->flightImportRepository->findOrCreateAirline(
                $airlineIata,
                $airlineName ?? $airlineIata
            );

            if ($this->flightImportRepository->flightExists($airline->id, $flightNumber)) {
                Log::channel('flights')->debug('FlightImportService: flight already exists, skipping', [
                    'airline_id' => $airline->id,
                    'flight_number' => $flightNumber,
                ]);

                return 'skipped';
            }

            $this->flightImportRepository->createFlight([
                'airline_id' => $airline->id,
                'flight_number' => $flightNumber,
                'airport_departure_id' => $departureAirport->id,
                'airport_arrival_id' => $arrivalAirport->id,
                'departure_time' => Carbon::parse($depScheduled)->format('H:i:s'),
                'arrival_time' => Carbon::parse($arrScheduled)->format('H:i:s'),
                'price' => rand(150, 3000),
                'scheduled_date' => now()->addDays(rand(1, 90))->toDateString(),
            ]);

            return 'processed';
        } catch (Throwable $e) {
            Log::channel('flights')->error('FlightImportService: error processing record', [
                'flight_number' => $record['flight']['number'] ?? 'unknown',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return 'error';
        }
    }

    /**
     * Resolve an airport by IATA code.
     * Checks the database first; falls back to airports.csv and persists the new record.
     */
    private function resolveAirport(string $iataCode): ?Airport
    {
        try {
            $airport = $this->flightImportRepository->findAirportByIata($iataCode);

            if ($airport !== null) {
                return $airport;
            }

            Log::channel('flights')->info('FlightImportService: airport not in database, searching CSV', [
                'iata_code' => $iataCode,
            ]);

            $csvRecord = $this->airportCsvRepository->findByIata($iataCode);

            if ($csvRecord === null) {
                Log::channel('flights')->warning('FlightImportService: airport not found in CSV either', [
                    'iata_code' => $iataCode,
                ]);

                return null;
            }

            $timezone = $this->deriveTimezone(
                $csvRecord['country_code'],
                $csvRecord['region_code']
            );

            return $this->flightImportRepository->createAirport([
                'iata_code' => $csvRecord['iata_code'],
                'name' => $csvRecord['name'],
                'city' => $csvRecord['city'],
                'city_code' => $csvRecord['iata_code'], // use IATA as city_code fallback
                'country_code' => $csvRecord['country_code'],
                'region_code' => $csvRecord['region_code'],
                'latitude' => $csvRecord['latitude'],
                'longitude' => $csvRecord['longitude'],
                'timezone' => $timezone,
            ]);
        } catch (Throwable $e) {
            Log::channel('flights')->error('FlightImportService: error resolving airport', [
                'iata_code' => $iataCode,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    /**
     * Derive a best-effort timezone from an ISO country code (and optional region).
     * Falls back to UTC if no match found.
     */
    private function deriveTimezone(string $countryCode, ?string $regionCode): string
    {
        try {
            if (! $countryCode) {
                return 'UTC';
            }

            $identifiers = \DateTimeZone::listIdentifiers(\DateTimeZone::PER_COUNTRY, $countryCode);

            if (empty($identifiers)) {
                return 'UTC';
            }

            // For large countries with multiple timezones, try to match the region
            if ($regionCode && count($identifiers) > 1) {
                $regionUpper = strtoupper($regionCode);

                foreach ($identifiers as $tz) {
                    if (str_contains(strtoupper($tz), $regionUpper)) {
                        return $tz;
                    }
                }
            }

            return $identifiers[0];
        } catch (Throwable $e) {
            Log::channel('flights')->warning('FlightImportService: could not derive timezone, defaulting to UTC', [
                'country_code' => $countryCode,
                'region_code' => $regionCode,
                'message' => $e->getMessage(),
            ]);

            return 'UTC';
        }
    }
}
