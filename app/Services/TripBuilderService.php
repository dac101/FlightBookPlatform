<?php

namespace App\Services;

use App\Enums\SegmentType;
use App\Enums\TripStatus;
use App\Enums\TripType;
use App\Models\Airport;
use App\Models\Trip;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class TripBuilderService
{
    public const DEFAULT_RADIUS_KM = 150;

    public const MAX_MULTI_CITY_SEGMENTS = 5;

    public function __construct(
        private readonly AirportService $airportService,
        private readonly FlightService $flightService,
        private readonly TripService $tripService,
    ) {}

    public function searchAirports(string $query, int $limit = 8): Collection
    {
        return $this->airportService->searchSuggestions($query, $limit);
    }

    /**
     * @param  array{
     *   departure_airport_id: int,
     *   arrival_airport_id: int,
     *   preferred_airline_ids?: array<int>,
     *   sort?: string,
     *   search?: string,
     *   radius_km?: float|int,
     *   page?: int
     * }  $criteria
     * @return array{
     *   requested_departure_airport: Airport,
     *   requested_arrival_airport: Airport,
     *   nearby_departure_airports: Collection<int, Airport>,
     *   nearby_arrival_airports: Collection<int, Airport>,
     *   flights: LengthAwarePaginator
     * }
     */
    public function searchFlights(array $criteria, int $perPage = 10): array
    {
        $radiusKm = (float) ($criteria['radius_km'] ?? self::DEFAULT_RADIUS_KM);
        $departureAirport = $this->airportService->find((int) $criteria['departure_airport_id']);
        $arrivalAirport = $this->airportService->find((int) $criteria['arrival_airport_id']);

        $nearbyDepartureAirports = $this->nearbyAirportsFor($departureAirport, $radiusKm);
        $nearbyArrivalAirports = $this->nearbyAirportsFor($arrivalAirport, $radiusKm);

        $flights = $this->flightService->searchForTripBuilder([
            'departure_airport_ids' => $nearbyDepartureAirports->pluck('id')->all(),
            'arrival_airport_ids' => $nearbyArrivalAirports->pluck('id')->all(),
            'preferred_airline_ids' => array_values($criteria['preferred_airline_ids'] ?? []),
            'sort' => $criteria['sort'] ?? 'price',
            'search' => $criteria['search'] ?? null,
            'page' => $criteria['page'] ?? 1,
            'scheduled_date_from' => $criteria['scheduled_date_from'] ?? null,
            'price_min' => $criteria['price_min'] ?? null,
            'price_max' => $criteria['price_max'] ?? null,
        ], $perPage);

        return [
            'requested_departure_airport' => $departureAirport,
            'requested_arrival_airport' => $arrivalAirport,
            'nearby_departure_airports' => $nearbyDepartureAirports->where('id', '!=', $departureAirport->id)->values(),
            'nearby_arrival_airports' => $nearbyArrivalAirports->where('id', '!=', $arrivalAirport->id)->values(),
            'flights' => $flights,
        ];
    }

    /**
     * @param  array{
     *   trip_name?: string|null,
     *   trip_type: string,
     *   radius_km?: float|int,
     *   legs: array<int, array{
     *     departure_airport_id: int,
     *     arrival_airport_id: int,
     *     departure_date: string,
     *     selected_flight_id: int
     *   }>
     * }  $payload
     */
    public function book(User $user, array $payload): Trip
    {
        $tripType = TripType::from($payload['trip_type']);
        $legs = $this->normalizeAndValidateLegs($tripType, $payload['legs']);
        $radiusKm = (float) ($payload['radius_km'] ?? self::DEFAULT_RADIUS_KM);

        $segments = [];
        $totalPrice = 0;

        foreach ($legs as $index => $leg) {
            $flight = $this->flightService->find((int) $leg['selected_flight_id']);

            $validDepartureIds = $this->nearbyAirportsFor(
                $this->airportService->find((int) $leg['departure_airport_id']),
                $radiusKm
            )->pluck('id')->all();

            $validArrivalIds = $this->nearbyAirportsFor(
                $this->airportService->find((int) $leg['arrival_airport_id']),
                $radiusKm
            )->pluck('id')->all();

            if (! in_array($flight->airport_departure_id, $validDepartureIds, true)
                || ! in_array($flight->airport_arrival_id, $validArrivalIds, true)) {
                throw ValidationException::withMessages([
                    "legs.$index.selected_flight_id" => 'The selected flight no longer matches this route search.',
                ]);
            }

            $totalPrice += (float) $flight->price;

            $segments[] = [
                'flight_id' => $flight->id,
                'segment_order' => $index + 1,
                'departure_date' => $leg['departure_date'],
                'segment_type' => $this->segmentTypeFor($tripType, $index, count($legs))->value,
            ];
        }

        $tripData = [
            'trip_name' => $payload['trip_name'] ?? null,
            'trip_type' => $tripType->value,
            'status' => TripStatus::Confirmed->value,
            'departure_date' => $legs[0]['departure_date'],
            'total_price_cache' => $totalPrice,
        ];

        if (! empty($payload['trip_id'])) {
            $trip = $this->tripService->find((int) $payload['trip_id']);

            if ($trip->user_id !== $user->id) {
                throw ValidationException::withMessages([
                    'trip_id' => 'You can only update your own trips.',
                ]);
            }

            return $this->tripService->rebuildWithSegments($trip, $tripData, $segments);
        }

        return $this->tripService->book($user, $tripData, $segments);
    }

    /**
     * @param  array{
     *   trip_name?: string|null,
     *   flight_id: int,
     *   departure_date: string
     * }  $payload
     */
    public function createTripFromFlight(User $user, array $payload): Trip
    {
        $flight = $this->flightService->find((int) $payload['flight_id']);
        $departureDate = $this->validatedSingleFlightDepartureDate($payload['departure_date']);

        return $this->tripService->book($user, [
            'trip_name' => $payload['trip_name'] ?? null,
            'trip_type' => TripType::OneWay->value,
            'status' => TripStatus::Pending->value,
            'departure_date' => $departureDate,
            'total_price_cache' => (float) $flight->price,
        ], [[
            'flight_id' => $flight->id,
            'segment_order' => 1,
            'departure_date' => $departureDate,
            'segment_type' => SegmentType::Outbound->value,
        ]]);
    }

    /**
     * @param  array{
     *   flight_id: int,
     *   departure_date: string
     * }  $payload
     */
    public function appendFlightToTrip(User $user, Trip $trip, array $payload): Trip
    {
        if ($trip->user_id !== $user->id) {
            throw ValidationException::withMessages([
                'trip_id' => 'You can only update your own trips.',
            ]);
        }

        if ($trip->status === TripStatus::Cancelled) {
            throw ValidationException::withMessages([
                'trip_id' => 'Cancelled trips cannot be updated.',
            ]);
        }

        $trip = $this->tripService->getWithSegments($trip);
        $flight = $this->flightService->find((int) $payload['flight_id']);
        $departureDate = $this->validatedSingleFlightDepartureDate($payload['departure_date']);
        $lastSegment = $trip->segments->sortBy('segment_order')->last();

        if (! $lastSegment?->flight) {
            throw ValidationException::withMessages([
                'trip_id' => 'This trip does not have a valid last segment to extend.',
            ]);
        }

        $lastSegmentDate = Carbon::parse($lastSegment->departure_date)->startOfDay();
        $newSegmentDate = Carbon::parse($departureDate)->startOfDay();

        if ($newSegmentDate->lt($lastSegmentDate)) {
            throw ValidationException::withMessages([
                'departure_date' => 'The added flight date must be on or after the last planned segment.',
            ]);
        }

        if ((int) $lastSegment->flight->airport_arrival_id !== (int) $flight->airport_departure_id) {
            throw ValidationException::withMessages([
                'flight_id' => 'The added flight must depart from the previous segment arrival airport.',
            ]);
        }

        return $this->tripService->appendSegment($trip, [
            'flight_id' => $flight->id,
            'departure_date' => $departureDate,
            'segment_type' => SegmentType::Connection->value,
        ], [
            'trip_type' => TripType::MultiCity->value,
            'status' => TripStatus::Pending->value,
        ]);
    }

    /**
     * @param  array{
     *   flight_id: int,
     *   departure_date: string
     * }  $payload
     */
    public function replaceFlightInOneWayTrip(User $user, Trip $trip, array $payload): Trip
    {
        if ($trip->user_id !== $user->id) {
            throw ValidationException::withMessages([
                'trip_id' => 'You can only update your own trips.',
            ]);
        }

        if ($trip->status === TripStatus::Cancelled) {
            throw ValidationException::withMessages([
                'trip_id' => 'Cancelled trips cannot be updated.',
            ]);
        }

        $flight = $this->flightService->find((int) $payload['flight_id']);
        $departureDate = $this->validatedSingleFlightDepartureDate($payload['departure_date']);

        return $this->tripService->replaceSegment($trip, [
            'flight_id' => $flight->id,
            'departure_date' => $departureDate,
            'segment_type' => SegmentType::Outbound->value,
        ], [
            'departure_date' => $departureDate,
            'total_price_cache' => (float) $flight->price,
        ]);
    }

    /**
     * @param  array<int, array{
     *   departure_airport_id: int,
     *   arrival_airport_id: int,
     *   departure_date: string,
     *   selected_flight_id: int
     * }>  $legs
     * @return array<int, array{
     *   departure_airport_id: int,
     *   arrival_airport_id: int,
     *   departure_date: string,
     *   selected_flight_id: int
     * }>
     */
    private function normalizeAndValidateLegs(TripType $tripType, array $legs): array
    {
        if ($tripType === TripType::OneWay && count($legs) !== 1) {
            throw ValidationException::withMessages(['legs' => 'A one-way trip must contain exactly one segment.']);
        }

        if (in_array($tripType, [TripType::RoundTrip, TripType::OpenJaw], true) && count($legs) !== 2) {
            throw ValidationException::withMessages(['legs' => 'This trip type must contain exactly two segments.']);
        }

        if ($tripType === TripType::MultiCity && (count($legs) < 2 || count($legs) > self::MAX_MULTI_CITY_SEGMENTS)) {
            throw ValidationException::withMessages(['legs' => 'A multi-city trip must contain between two and five segments.']);
        }

        $today = now()->startOfDay();
        $maxDate = now()->addDays(365)->endOfDay();

        foreach ($legs as $index => $leg) {
            $date = Carbon::parse($leg['departure_date'])->startOfDay();

            if ($index === 0 && ($date->lt($today) || $date->gt($maxDate))) {
                throw ValidationException::withMessages([
                    "legs.$index.departure_date" => 'The first departure date must be between today and 365 days from today.',
                ]);
            }

            if ($index > 0) {
                $previousDate = Carbon::parse($legs[$index - 1]['departure_date'])->startOfDay();

                if ($date->lt($previousDate)) {
                    throw ValidationException::withMessages([
                        "legs.$index.departure_date" => 'Each subsequent segment date must be on or after the prior segment.',
                    ]);
                }
            }
        }

        if ($tripType === TripType::RoundTrip) {
            if ((int) $legs[1]['departure_airport_id'] !== (int) $legs[0]['arrival_airport_id']
                || (int) $legs[1]['arrival_airport_id'] !== (int) $legs[0]['departure_airport_id']) {
                throw ValidationException::withMessages([
                    'legs' => 'A round-trip must return from the first arrival airport back to the original departure airport.',
                ]);
            }
        }

        if ($tripType === TripType::OpenJaw) {
            if ((int) $legs[1]['arrival_airport_id'] !== (int) $legs[0]['departure_airport_id']) {
                throw ValidationException::withMessages([
                    'legs' => 'An open-jaw trip must end back at the original departure airport.',
                ]);
            }
        }

        if ($tripType === TripType::MultiCity) {
            foreach ($legs as $index => $leg) {
                if ($index === 0) {
                    continue;
                }

                if ((int) $leg['departure_airport_id'] !== (int) $legs[$index - 1]['arrival_airport_id']) {
                    throw ValidationException::withMessages([
                        "legs.$index.departure_airport_id" => 'Each multi-city leg must depart from the previous leg arrival airport.',
                    ]);
                }
            }
        }

        return $legs;
    }

    private function segmentTypeFor(TripType $tripType, int $index, int $count): SegmentType
    {
        return match ($tripType) {
            TripType::OneWay => SegmentType::Outbound,
            TripType::RoundTrip, TripType::OpenJaw => $index === 0 ? SegmentType::Outbound : SegmentType::Return,
            TripType::MultiCity => $index === 0 ? SegmentType::Outbound : SegmentType::Connection,
        };
    }

    private function validatedSingleFlightDepartureDate(string $value): string
    {
        $date = Carbon::parse($value)->startOfDay();
        $today = now()->startOfDay();
        $maxDate = now()->addDays(365)->startOfDay();

        if ($date->lt($today) || $date->gt($maxDate)) {
            throw ValidationException::withMessages([
                'departure_date' => 'The departure date must be between today and 365 days from today.',
            ]);
        }

        return $date->toDateString();
    }

    private function nearbyAirportsFor(Airport $airport, float $radiusKm): Collection
    {
        return $this->airportService
            ->findNearby((float) $airport->latitude, (float) $airport->longitude, $radiusKm, 12)
            ->prepend($airport)
            ->unique('id')
            ->values();
    }
}
