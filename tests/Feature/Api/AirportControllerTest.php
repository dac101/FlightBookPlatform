<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsFlightData;
use Tests\TestCase;

class AirportControllerTest extends TestCase
{
    use BuildsFlightData;
    use RefreshDatabase;

    public function test_airports_can_be_listed_and_viewed(): void
    {
        $airport = $this->createAirport(['name' => 'Public Airport']);
        $this->createFlight(['airport_departure_id' => $airport->id]);

        $this->getJson('/api/v1/airports')
            ->assertOk()
            ->assertJsonFragment(['id' => $airport->id, 'name' => 'Public Airport']);

        $this->getJson('/api/v1/airports/'.$airport->id)
            ->assertOk()
            ->assertJsonFragment(['id' => $airport->id, 'name' => 'Public Airport']);
    }
}
