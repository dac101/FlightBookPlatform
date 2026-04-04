<?php

namespace App\Repositories\Contracts;

use App\Models\Airline;
use App\Models\Airport;
use App\Models\Flight;

interface FlightImportRepositoryInterface
{
    public function findAirportByIata(string $iataCode): ?Airport;

    /**
     * @param  array{iata_code: string, name: string, city: string, city_code: string, country_code: string, region_code: string|null, latitude: float, longitude: float, timezone: string}  $data
     */
    public function createAirport(array $data): Airport;

    public function findOrCreateAirline(string $iataCode, string $name): Airline;

    public function flightExists(int $airlineId, string $flightNumber): bool;

    /**
     * @param  array{airline_id: int, flight_number: string, airport_departure_id: int, airport_arrival_id: int, departure_time: string, arrival_time: string, price: float}  $data
     */
    public function createFlight(array $data): Flight;
}
