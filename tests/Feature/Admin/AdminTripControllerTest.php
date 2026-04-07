<?php

namespace Tests\Feature\Admin;

use App\Enums\SegmentType;
use App\Enums\TripStatus;
use App\Enums\TripType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsFlightData;
use Tests\TestCase;

class AdminTripControllerTest extends TestCase
{
    use BuildsFlightData;
    use RefreshDatabase;

    public function test_admin_can_list_and_show_trips(): void
    {
        $admin = $this->createAdminUser();
        $trip = $this->createTrip(null, ['trip_name' => 'Summer Escape']);
        $this->createTripSegment($trip, $this->createFlight(), [
            'segment_type' => SegmentType::Outbound,
        ]);

        $this->actingAs($admin)
            ->getJson(route('admin.api.trips.index', ['search' => 'Summer']))
            ->assertOk()
            ->assertJsonFragment(['id' => $trip->id, 'trip_name' => 'Summer Escape']);

        $this->actingAs($admin)
            ->getJson(route('admin.api.trips.show', $trip->id))
            ->assertOk()
            ->assertJsonFragment(['id' => $trip->id, 'trip_name' => 'Summer Escape']);
    }

    public function test_admin_can_update_and_delete_trips(): void
    {
        $admin = $this->createAdminUser();
        $trip = $this->createTrip();

        $this->actingAs($admin)
            ->patchJson(route('admin.api.trips.update', $trip->id), [
                'trip_name' => 'Updated Trip',
                'trip_type' => TripType::RoundTrip->value,
                'status' => TripStatus::Confirmed->value,
            ])
            ->assertOk()
            ->assertJsonPath('data.trip_name', 'Updated Trip')
            ->assertJsonPath('data.trip_type', TripType::RoundTrip->value);

        $this->actingAs($admin)
            ->deleteJson(route('admin.api.trips.destroy', $trip->id))
            ->assertOk()
            ->assertJsonPath('message', 'Trip deleted successfully.');
    }
}
