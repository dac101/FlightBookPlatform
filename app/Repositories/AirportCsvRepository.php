<?php

namespace App\Repositories;

use App\Repositories\Contracts\AirportCsvRepositoryInterface;
use Illuminate\Support\Facades\Log;
use Throwable;

class AirportCsvRepository implements AirportCsvRepositoryInterface
{
    private const CSV_PATH = 'airports.csv';

    /**
     * Column index map derived from the CSV header row.
     * Matches: id,ident,type,name,latitude_deg,longitude_deg,elevation_ft,
     *          continent,iso_country,iso_region,municipality,scheduled_service,
     *          icao_code,iata_code,gps_code,local_code,home_link,wikipedia_link,keywords
     */
    private const COL_NAME = 3;

    private const COL_LATITUDE = 4;

    private const COL_LONGITUDE = 5;

    private const COL_ISO_COUNTRY = 8;

    private const COL_ISO_REGION = 9;

    private const COL_MUNICIPALITY = 10;

    private const COL_IATA_CODE = 13;

    /**
     * Search airports.csv line by line for the given IATA code.
     * Memory-efficient: never loads the full file into memory.
     *
     * @return array{iata_code: string, name: string, city: string, country_code: string, region_code: string|null, latitude: float, longitude: float}|null
     */
    public function findByIata(string $iataCode): ?array
    {
        try {
            $csvPath = public_path(self::CSV_PATH);

            if (! file_exists($csvPath)) {
                Log::channel('flights')->error('AirportCsvRepository: CSV file not found', ['path' => $csvPath]);

                return null;
            }

            $handle = fopen($csvPath, 'r');

            if ($handle === false) {
                Log::channel('flights')->error('AirportCsvRepository: failed to open CSV file', ['path' => $csvPath]);

                return null;
            }

            // Skip the header row
            fgetcsv($handle);

            $iataUpper = strtoupper(trim($iataCode));

            while (($row = fgetcsv($handle)) !== false) {
                if (! isset($row[self::COL_IATA_CODE])) {
                    continue;
                }

                if (strtoupper(trim($row[self::COL_IATA_CODE])) !== $iataUpper) {
                    continue;
                }

                fclose($handle);

                $isoRegion = trim($row[self::COL_ISO_REGION] ?? '');
                $regionCode = str_contains($isoRegion, '-')
                    ? explode('-', $isoRegion, 2)[1]
                    : ($isoRegion ?: null);

                $countryCode = strtoupper(trim($row[self::COL_ISO_COUNTRY] ?? ''));

                Log::channel('flights')->info('AirportCsvRepository: airport found in CSV', [
                    'iata_code' => $iataUpper,
                    'name' => $row[self::COL_NAME],
                ]);

                return [
                    'iata_code' => $iataUpper,
                    'name' => trim($row[self::COL_NAME]),
                    'city' => trim($row[self::COL_MUNICIPALITY]) ?: $iataUpper,
                    'country_code' => $countryCode,
                    'region_code' => $regionCode,
                    'latitude' => (float) $row[self::COL_LATITUDE],
                    'longitude' => (float) $row[self::COL_LONGITUDE],
                ];
            }

            fclose($handle);

            Log::channel('flights')->warning('AirportCsvRepository: IATA code not found in CSV', ['iata_code' => $iataUpper]);

            return null;
        } catch (Throwable $e) {
            Log::channel('flights')->error('AirportCsvRepository: unexpected error while searching CSV', [
                'iata_code' => $iataCode,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }
}
