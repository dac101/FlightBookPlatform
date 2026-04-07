<?php

namespace Tests\Concerns;

use App\Enums\SegmentType;
use App\Enums\TripStatus;
use App\Enums\TripType;
use App\Enums\UserRole;
use App\Models\Airline;
use App\Models\Airport;
use App\Models\Flight;
use App\Models\Trip;
use App\Models\TripSegment;
use App\Models\User;

trait BuildsFlightData
{
    protected function createUser(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'role' => UserRole::User,
            'seen_tutorial_at' => now(),
        ], $attributes));
    }

    protected function createAdminUser(array $attributes = []): User
    {
        return $this->createUser(array_merge([
            'role' => UserRole::Admin,
        ], $attributes));
    }

    protected function createAirline(array $attributes = []): Airline
    {
        return Airline::query()->create(array_merge([
            'name' => 'Airline '.$this->uniqueToken('airline'),
            'iata_code' => $this->uniqueCode('A'),
        ], $attributes));
    }

    protected function createAirport(array $attributes = []): Airport
    {
        $code = $this->uniqueCode('Y');

        return Airport::query()->create(array_merge([
            'iata_code' => $code,
            'name' => 'Airport '.$code,
            'city' => 'City '.$code,
            'city_code' => $code,
            'country_code' => 'CA',
            'region_code' => 'ON',
            'latitude' => 43.6532,
            'longitude' => -79.3832,
            'timezone' => 'America/Toronto',
        ], $attributes));
    }

    protected function createFlight(array $attributes = []): Flight
    {
        $departureAirport = isset($attributes['airport_departure_id'])
            ? Airport::query()->findOrFail($attributes['airport_departure_id'])
            : $this->createAirport();
        $arrivalAirport = isset($attributes['airport_arrival_id'])
            ? Airport::query()->findOrFail($attributes['airport_arrival_id'])
            : $this->createAirport();
        $airline = isset($attributes['airline_id'])
            ? Airline::query()->findOrFail($attributes['airline_id'])
            : $this->createAirline();

        unset($attributes['airport_departure_id'], $attributes['airport_arrival_id'], $attributes['airline_id']);

        return Flight::query()->create(array_merge([
            'flight_number' => 'FL'.$this->uniqueToken('flight'),
            'airline_id' => $airline->id,
            'airport_departure_id' => $departureAirport->id,
            'airport_arrival_id' => $arrivalAirport->id,
            'departure_time' => '09:30:00',
            'arrival_time' => '11:45:00',
            'scheduled_date' => now()->addDays(10)->toDateString(),
            'price' => 199.99,
        ], $attributes));
    }

    protected function createTrip(?User $user = null, array $attributes = []): Trip
    {
        $user ??= $this->createUser();

        return Trip::query()->create(array_merge([
            'user_id' => $user->id,
            'trip_name' => 'Trip '.$this->uniqueToken('trip'),
            'trip_type' => TripType::OneWay,
            'status' => TripStatus::Pending,
            'departure_date' => now()->addDays(10)->toDateString(),
            'total_price_cache' => 199.99,
        ], $attributes));
    }

    protected function createTripSegment(Trip $trip, ?Flight $flight = null, array $attributes = []): TripSegment
    {
        $flight ??= $this->createFlight();

        return TripSegment::query()->create(array_merge([
            'trip_id' => $trip->id,
            'flight_id' => $flight->id,
            'segment_order' => 1,
            'departure_date' => $trip->departure_date,
            'segment_type' => SegmentType::Outbound,
        ], $attributes));
    }

    private function uniqueCode(string $prefix): string
    {
        static $sequence = 0;

        $sequence++;

        return strtoupper(substr($prefix, 0, 1).str_pad(base_convert($sequence, 10, 36), 2, '0', STR_PAD_LEFT));
    }

    private function uniqueToken(string $prefix): string
    {
        static $sequence = 0;

        $sequence++;

        return strtolower($prefix).'-'.$sequence;
    }
}
