<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsFlightData;
use Tests\TestCase;

class FlightControllerTest extends TestCase
{
    use BuildsFlightData;
    use RefreshDatabase;

    public function test_flights_can_be_filtered_and_viewed(): void
    {
        $departureAirport = $this->createAirport(['city' => 'Montreal']);
        $arrivalAirport = $this->createAirport(['city' => 'Toronto']);
        $airline = $this->createAirline(['name' => 'API Airline']);
        $flight = $this->createFlight([
            'flight_number' => 'API123',
            'airline_id' => $airline->id,
            'airport_departure_id' => $departureAirport->id,
            'airport_arrival_id' => $arrivalAirport->id,
        ]);

        $this->getJson('/api/v1/flights?departure=Montreal&arrival=Toronto&airline=API')
            ->assertOk()
            ->assertJsonFragment(['id' => $flight->id, 'flight_number' => 'API123']);

        $this->getJson('/api/v1/flights/'.$flight->id)
            ->assertOk()
            ->assertJsonFragment(['id' => $flight->id, 'flight_number' => 'API123']);
    }
}
