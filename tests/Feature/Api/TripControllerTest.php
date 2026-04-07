<?php

namespace Tests\Feature\Api;

use App\Enums\TripStatus;
use App\Enums\TripType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsFlightData;
use Tests\TestCase;

class TripControllerTest extends TestCase
{
    use BuildsFlightData;
    use RefreshDatabase;

    public function test_authenticated_users_can_crud_their_trips_over_the_api(): void
    {
        $user = $this->createUser();
        $this->actingAs($user, 'sanctum');

        $createdResponse = $this->postJson('/api/v1/trips', [
            'trip_name' => 'API Trip',
            'trip_type' => TripType::OneWay->value,
            'departure_date' => now()->addDays(10)->toDateString(),
        ]);

        $createdResponse->assertCreated()
            ->assertJsonPath('trip_name', 'API Trip');

        $tripId = $createdResponse->json('id');

        $this->getJson('/api/v1/trips')
            ->assertOk()
            ->assertJsonFragment(['id' => $tripId, 'trip_name' => 'API Trip']);

        $this->getJson('/api/v1/trips/'.$tripId)
            ->assertOk()
            ->assertJsonFragment(['id' => $tripId, 'trip_name' => 'API Trip']);

        $this->putJson('/api/v1/trips/'.$tripId, [
            'trip_name' => 'Updated API Trip',
            'status' => TripStatus::Confirmed->value,
        ])
            ->assertOk()
            ->assertJsonPath('trip_name', 'Updated API Trip');

        $this->deleteJson('/api/v1/trips/'.$tripId)
            ->assertNoContent();
    }

    public function test_users_cannot_view_another_users_trip(): void
    {
        $owner = $this->createUser();
        $viewer = $this->createUser();
        $trip = $this->createTrip($owner);

        $this->actingAs($viewer, 'sanctum');

        $this->getJson('/api/v1/trips/'.$trip->id)
            ->assertForbidden();
    }
}
