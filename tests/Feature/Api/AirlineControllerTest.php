<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsFlightData;
use Tests\TestCase;

class AirlineControllerTest extends TestCase
{
    use BuildsFlightData;
    use RefreshDatabase;

    public function test_airlines_can_be_listed_and_viewed(): void
    {
        $airline = $this->createAirline(['name' => 'Public Airline']);
        $this->createFlight(['airline_id' => $airline->id]);

        $this->getJson('/api/v1/airlines')
            ->assertOk()
            ->assertJsonFragment(['id' => $airline->id, 'name' => 'Public Airline']);

        $this->getJson('/api/v1/airlines/'.$airline->id)
            ->assertOk()
            ->assertJsonFragment(['id' => $airline->id, 'name' => 'Public Airline']);
    }
}
