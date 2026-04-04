<?php

namespace App\Repositories\Contracts;

interface AirportCsvRepositoryInterface
{
    /**
     * Search the airports CSV line by line for a matching IATA code.
     * Returns an associative array of the CSV row, or null if not found.
     *
     * @return array{iata_code: string, name: string, city: string, country_code: string, region_code: string|null, latitude: float, longitude: float}|null
     */
    public function findByIata(string $iataCode): ?array;
}
