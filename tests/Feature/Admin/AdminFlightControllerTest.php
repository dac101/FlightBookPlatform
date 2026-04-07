<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsFlightData;
use Tests\TestCase;

class AdminFlightControllerTest extends TestCase
{
    use BuildsFlightData;
    use RefreshDatabase;

    public function test_admin_can_list_and_show_flights(): void
    {
        $admin = $this->createAdminUser();
        $flight = $this->createFlight(['flight_number' => 'AC101']);

        $this->actingAs($admin)
            ->getJson(route('admin.api.flights.index', ['search' => 'AC101']))
            ->assertOk()
            ->assertJsonFragment(['id' => $flight->id, 'flight_number' => 'AC101']);

        $this->actingAs($admin)
            ->getJson(route('admin.api.flights.show', $flight->id))
            ->assertOk()
            ->assertJsonFragment(['id' => $flight->id, 'flight_number' => 'AC101']);
    }

    public function test_admin_can_create_update_and_delete_flights(): void
    {
        $admin = $this->createAdminUser();
        $airline = $this->createAirline();
        $departureAirport = $this->createAirport();
        $arrivalAirport = $this->createAirport();

        $createdResponse = $this->actingAs($admin)
            ->postJson(route('admin.api.flights.store'), [
                'flight_number' => 'TS123',
                'airline_id' => $airline->id,
                'airport_departure_id' => $departureAirport->id,
                'airport_arrival_id' => $arrivalAirport->id,
                'departure_time' => '08:00',
                'arrival_time' => '10:00',
                'price' => 321.45,
            ]);

        $createdResponse->assertCreated()
            ->assertJsonPath('data.flight_number', 'TS123');

        $flightId = $createdResponse->json('data.id');

        $this->actingAs($admin)
            ->patchJson(route('admin.api.flights.update', $flightId), [
                'price' => 111.00,
            ])
            ->assertOk()
            ->assertJsonPath('data.price', '111.00');

        $this->actingAs($admin)
            ->deleteJson(route('admin.api.flights.destroy', $flightId))
            ->assertOk()
            ->assertJsonPath('message', 'Flight deleted successfully.');
    }
}
