<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsFlightData;
use Tests\TestCase;

class AdminAirlineControllerTest extends TestCase
{
    use BuildsFlightData;
    use RefreshDatabase;

    public function test_admin_can_list_options_and_show_airlines(): void
    {
        $admin = $this->createAdminUser();
        $airline = $this->createAirline(['name' => 'Sky Test']);

        $this->actingAs($admin)
            ->getJson(route('admin.api.airlines.index', ['search' => 'Sky']))
            ->assertOk()
            ->assertJsonFragment(['id' => $airline->id, 'name' => 'Sky Test']);

        $this->actingAs($admin)
            ->getJson(route('admin.api.airlines.options'))
            ->assertOk()
            ->assertJsonFragment(['id' => $airline->id, 'name' => 'Sky Test']);

        $this->actingAs($admin)
            ->getJson(route('admin.api.airlines.show', $airline->id))
            ->assertOk()
            ->assertJsonFragment(['id' => $airline->id, 'name' => 'Sky Test']);
    }

    public function test_admin_can_create_update_and_delete_airlines(): void
    {
        $admin = $this->createAdminUser();

        $createdResponse = $this->actingAs($admin)
            ->postJson(route('admin.api.airlines.store'), [
                'name' => 'New Airline',
                'iata_code' => 'NAL',
            ]);

        $createdResponse->assertCreated()
            ->assertJsonPath('data.name', 'New Airline');

        $airlineId = $createdResponse->json('data.id');

        $this->actingAs($admin)
            ->patchJson(route('admin.api.airlines.update', $airlineId), [
                'name' => 'Renamed Airline',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Renamed Airline');

        $this->actingAs($admin)
            ->deleteJson(route('admin.api.airlines.destroy', $airlineId))
            ->assertOk()
            ->assertJsonPath('message', 'Airline deleted successfully.');
    }
}
