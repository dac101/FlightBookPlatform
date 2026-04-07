<?php

namespace Tests\Unit\Services;

use App\Enums\TripStatus;
use App\Models\Airport;
use App\Models\Flight;
use App\Models\Trip;
use App\Models\TripSegment;
use App\Models\User;
use App\Services\AirportService;
use App\Services\FlightService;
use App\Services\TripBuilderService;
use App\Services\TripService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tests\TestCase;

class TripBuilderServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private AirportService $airportService;

    private FlightService $flightService;

    private TripService $tripService;

    private TripBuilderService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->airportService = Mockery::mock(AirportService::class);
        $this->flightService = Mockery::mock(FlightService::class);
        $this->tripService = Mockery::mock(TripService::class);
        $this->service = new TripBuilderService($this->airportService, $this->flightService, $this->tripService);

        config()->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
    }

    public function test_search_airports_delegates_to_airport_service(): void
    {
        $collection = new EloquentCollection([$this->makeAirport(1, 'YUL', 45.47, -73.74)]);

        $this->airportService->shouldReceive('searchSuggestions')->once()->with('Montreal', 5)->andReturn($collection);

        $this->assertSame($collection, $this->service->searchAirports('Montreal', 5));
    }

    public function test_search_flights_builds_trip_builder_criteria(): void
    {
        $departure = $this->makeAirport(1, 'YUL', 45.47, -73.74);
        $arrival = $this->makeAirport(2, 'YYZ', 43.67, -79.63);
        $nearbyDeparture = $this->makeAirport(3, 'YMX', 45.68, -74.03);
        $nearbyArrival = $this->makeAirport(4, 'YTZ', 43.62, -79.40);
        $paginator = new LengthAwarePaginator([], 0, 10, 1);

        $this->airportService->shouldReceive('find')->once()->with(1)->andReturn($departure);
        $this->airportService->shouldReceive('find')->once()->with(2)->andReturn($arrival);
        $this->airportService->shouldReceive('findNearby')->once()->with(45.47, -73.74, 200.0, 12)->andReturn(new EloquentCollection([$nearbyDeparture]));
        $this->airportService->shouldReceive('findNearby')->once()->with(43.67, -79.63, 200.0, 12)->andReturn(new EloquentCollection([$nearbyArrival]));
        $this->flightService->shouldReceive('searchForTripBuilder')->once()->with([
            'departure_airport_ids' => [1, 3],
            'arrival_airport_ids' => [2, 4],
            'preferred_airline_ids' => [7],
            'sort' => 'price',
            'search' => 'AC',
            'page' => 2,
            'scheduled_date_from' => null,
            'price_min' => null,
            'price_max' => null,
        ], 10)->andReturn($paginator);

        $result = $this->service->searchFlights([
            'departure_airport_id' => 1,
            'arrival_airport_id' => 2,
            'preferred_airline_ids' => [7],
            'sort' => 'price',
            'search' => 'AC',
            'radius_km' => 200,
            'page' => 2,
        ]);

        $this->assertSame($departure, $result['requested_departure_airport']);
        $this->assertSame($arrival, $result['requested_arrival_airport']);
        $this->assertSame($paginator, $result['flights']);
        $this->assertCount(1, $result['nearby_departure_airports']);
        $this->assertSame(3, $result['nearby_departure_airports']->first()->id);
    }

    public function test_book_creates_trip_from_valid_legs(): void
    {
        $user = new User(['name' => 'John Doe']);
        $date = now()->addDay()->toDateString();
        $departure = $this->makeAirport(1, 'YUL', 45.47, -73.74);
        $arrival = $this->makeAirport(2, 'YYZ', 43.67, -79.63);
        $flight = new Flight(['price' => 199.99]);
        $flight->id = 10;
        $flight->airport_departure_id = 1;
        $flight->airport_arrival_id = 2;
        $trip = new Trip(['trip_name' => 'Work Trip']);

        $this->flightService->shouldReceive('find')->once()->with(10)->andReturn($flight);
        $this->airportService->shouldReceive('find')->once()->with(1)->andReturn($departure);
        $this->airportService->shouldReceive('findNearby')->once()->with(45.47, -73.74, 150.0, 12)->andReturn(new EloquentCollection);
        $this->airportService->shouldReceive('find')->once()->with(2)->andReturn($arrival);
        $this->airportService->shouldReceive('findNearby')->once()->with(43.67, -79.63, 150.0, 12)->andReturn(new EloquentCollection);
        $this->tripService->shouldReceive('book')->once()->with($user, Mockery::on(function (array $tripData) use ($date): bool {
            return $tripData['trip_name'] === 'Work Trip'
                && $tripData['trip_type'] === 'one_way'
                && $tripData['status'] === 'confirmed'
                && $tripData['departure_date'] === $date
                && (float) $tripData['total_price_cache'] === 199.99;
        }), Mockery::on(function (array $segments) use ($date): bool {
            return count($segments) === 1
                && $segments[0]['flight_id'] === 10
                && $segments[0]['segment_order'] === 1
                && $segments[0]['departure_date'] === $date
                && $segments[0]['segment_type'] === 'outbound';
        }))->andReturn($trip);

        $this->assertSame($trip, $this->service->book($user, [
            'trip_name' => 'Work Trip',
            'trip_type' => 'one_way',
            'legs' => [[
                'departure_airport_id' => 1,
                'arrival_airport_id' => 2,
                'departure_date' => $date,
                'selected_flight_id' => 10,
            ]],
        ]));
    }

    public function test_create_trip_from_flight_books_pending_one_way_trip(): void
    {
        $user = new User(['name' => 'John Doe']);
        $date = now()->addDay()->toDateString();
        $flight = new Flight(['price' => 299.50]);
        $flight->id = 12;

        $this->flightService->shouldReceive('find')->once()->with(12)->andReturn($flight);
        $this->tripService->shouldReceive('book')->once()->with($user, Mockery::on(function (array $tripData) use ($date): bool {
            return $tripData['trip_name'] === 'Vacation'
                && $tripData['trip_type'] === 'one_way'
                && $tripData['status'] === 'pending'
                && $tripData['departure_date'] === $date
                && (float) $tripData['total_price_cache'] === 299.50;
        }), Mockery::type('array'))->andReturn(new Trip);

        $this->service->createTripFromFlight($user, [
            'trip_name' => 'Vacation',
            'flight_id' => 12,
            'departure_date' => $date,
        ]);

        $this->assertTrue(true);
    }

    public function test_append_flight_to_trip_adds_connection_segment(): void
    {
        $user = new User;
        $user->id = 1;

        $trip = new Trip(['status' => TripStatus::Pending->value]);
        $trip->user_id = 1;

        $lastFlight = new Flight;
        $lastFlight->airport_arrival_id = 5;

        $segment = new TripSegment(['segment_order' => 1, 'departure_date' => now()->addDay()->toDateString()]);
        $segment->setRelation('flight', $lastFlight);

        $tripWithSegments = clone $trip;
        $tripWithSegments->setRelation('segments', new EloquentCollection([$segment]));

        $newFlight = new Flight(['price' => 410.00]);
        $newFlight->id = 22;
        $newFlight->airport_departure_id = 5;

        $this->tripService->shouldReceive('getWithSegments')->once()->with($trip)->andReturn($tripWithSegments);
        $this->flightService->shouldReceive('find')->once()->with(22)->andReturn($newFlight);
        $this->tripService->shouldReceive('appendSegment')->once()->with($tripWithSegments, Mockery::on(function (array $segmentData): bool {
            return $segmentData['flight_id'] === 22 && $segmentData['segment_type'] === 'connection';
        }), [
            'trip_type' => 'multi_city',
            'status' => 'pending',
        ])->andReturn($tripWithSegments);

        $this->assertSame($tripWithSegments, $this->service->appendFlightToTrip($user, $trip, [
            'flight_id' => 22,
            'departure_date' => now()->addDays(2)->toDateString(),
        ]));
    }

    public function test_replace_flight_in_one_way_trip_replaces_outbound_segment(): void
    {
        $user = new User;
        $user->id = 1;

        $trip = new Trip(['status' => TripStatus::Pending->value]);
        $trip->user_id = 1;

        $flight = new Flight(['price' => 355.25]);
        $flight->id = 41;

        $this->flightService->shouldReceive('find')->once()->with(41)->andReturn($flight);
        $this->tripService->shouldReceive('replaceSegment')->once()->with($trip, [
            'flight_id' => 41,
            'departure_date' => now()->addDay()->toDateString(),
            'segment_type' => 'outbound',
        ], [
            'departure_date' => now()->addDay()->toDateString(),
            'total_price_cache' => 355.25,
        ])->andReturn($trip);

        $this->assertSame($trip, $this->service->replaceFlightInOneWayTrip($user, $trip, [
            'flight_id' => 41,
            'departure_date' => now()->addDay()->toDateString(),
        ]));
    }

    public function test_append_flight_to_trip_throws_for_wrong_user(): void
    {
        $user = new User;
        $user->id = 1;

        $trip = new Trip(['status' => TripStatus::Pending->value]);
        $trip->user_id = 2;

        $this->expectException(ValidationException::class);

        $this->service->appendFlightToTrip($user, $trip, [
            'flight_id' => 22,
            'departure_date' => now()->addDay()->toDateString(),
        ]);
    }

    private function makeAirport(int $id, string $iata, float $lat, float $lng): Airport
    {
        $airport = new Airport([
            'name' => $iata.' Airport',
            'iata_code' => $iata,
            'city' => $iata,
            'city_code' => $iata,
            'country_code' => 'CA',
            'latitude' => $lat,
            'longitude' => $lng,
            'timezone' => 'America/Toronto',
        ]);
        $airport->id = $id;

        return $airport;
    }
}
