<?php

namespace Tests\Feature\Api;

use App\Enums\TripType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsFlightData;
use Tests\TestCase;

class ClientTripBuilderControllerTest extends TestCase
{
    use BuildsFlightData;
    use RefreshDatabase;

    public function test_authenticated_users_can_fetch_builder_reference_data(): void
    {
        $user = $this->createUser();
        $airport = $this->createAirport(['name' => 'Builder Airport']);
        $airline = $this->createAirline(['name' => 'Builder Airline']);

        $this->actingAs($user)
            ->getJson(route('client-api.airports.search', ['query' => 'Builder']))
            ->assertOk()
            ->assertJsonFragment(['id' => $airport->id, 'name' => 'Builder Airport']);

        $this->actingAs($user)
            ->getJson(route('client-api.airports.map-data'))
            ->assertOk()
            ->assertJsonFragment(['id' => $airport->id, 'name' => 'Builder Airport']);

        $this->actingAs($user)
            ->getJson(route('client-api.airlines.options'))
            ->assertOk()
            ->assertJsonFragment(['id' => $airline->id, 'name' => 'Builder Airline']);
    }

    public function test_authenticated_users_can_search_and_explore_flights(): void
    {
        $user = $this->createUser();
        $airline = $this->createAirline();
        $departureAirport = $this->createAirport(['city' => 'Departure City']);
        $arrivalAirport = $this->createAirport(['city' => 'Arrival City']);
        $flight = $this->createFlight([
            'airline_id' => $airline->id,
            'airport_departure_id' => $departureAirport->id,
            'airport_arrival_id' => $arrivalAirport->id,
            'flight_number' => 'BLD123',
        ]);

        $this->actingAs($user)
            ->postJson(route('client-api.trip-builder.search'), [
                'departure_airport_id' => $departureAirport->id,
                'arrival_airport_id' => $arrivalAirport->id,
                'departure_date' => now()->addDays(10)->toDateString(),
                'preferred_airline_ids' => [$airline->id],
            ])
            ->assertOk()
            ->assertJsonFragment(['id' => $flight->id, 'flight_number' => 'BLD123']);

        $this->actingAs($user)
            ->getJson(route('client-api.flights.index', ['search' => 'BLD123']))
            ->assertOk()
            ->assertJsonFragment(['id' => $flight->id, 'flight_number' => 'BLD123']);
    }

    public function test_authenticated_users_can_book_a_trip_from_builder_payload(): void
    {
        $user = $this->createUser();
        $departureAirport = $this->createAirport();
        $arrivalAirport = $this->createAirport();
        $flight = $this->createFlight([
            'airport_departure_id' => $departureAirport->id,
            'airport_arrival_id' => $arrivalAirport->id,
        ]);

        $this->actingAs($user)
            ->postJson(route('client-api.trip-builder.book'), [
                'trip_name' => 'Booked Builder Trip',
                'trip_type' => TripType::OneWay->value,
                'legs' => [[
                    'departure_airport_id' => $departureAirport->id,
                    'arrival_airport_id' => $arrivalAirport->id,
                    'selected_flight_id' => $flight->id,
                ]],
            ])
            ->assertCreated()
            ->assertJsonPath('data.trip_name', 'Booked Builder Trip')
            ->assertJsonPath('data.trip_type', TripType::OneWay->value);
    }
}
