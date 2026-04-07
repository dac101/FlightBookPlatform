<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsFlightData;
use Tests\TestCase;

class AdminAirportControllerTest extends TestCase
{
    use BuildsFlightData;
    use RefreshDatabase;

    public function test_admin_can_list_options_and_show_airports(): void
    {
        $admin = $this->createAdminUser();
        $airport = $this->createAirport(['name' => 'Toronto Test']);

        $this->actingAs($admin)
            ->getJson(route('admin.api.airports.index', ['search' => 'Toronto']))
            ->assertOk()
            ->assertJsonFragment(['id' => $airport->id, 'name' => 'Toronto Test']);

        $this->actingAs($admin)
            ->getJson(route('admin.api.airports.options'))
            ->assertOk()
            ->assertJsonFragment(['id' => $airport->id, 'name' => 'Toronto Test']);

        $this->actingAs($admin)
            ->getJson(route('admin.api.airports.show', $airport->id))
            ->assertOk()
            ->assertJsonFragment(['id' => $airport->id, 'name' => 'Toronto Test']);
    }

    public function test_admin_can_create_update_and_delete_airports(): void
    {
        $admin = $this->createAdminUser();

        $createdResponse = $this->actingAs($admin)
            ->postJson(route('admin.api.airports.store'), [
                'iata_code' => 'ZZA',
                'name' => 'Created Airport',
                'city' => 'Created City',
                'city_code' => 'CCA',
                'country_code' => 'CA',
                'region_code' => 'ON',
                'latitude' => 43.7000,
                'longitude' => -79.4000,
                'timezone' => 'America/Toronto',
            ]);

        $createdResponse->assertCreated()
            ->assertJsonPath('data.name', 'Created Airport');

        $airportId = $createdResponse->json('data.id');

        $this->actingAs($admin)
            ->patchJson(route('admin.api.airports.update', $airportId), [
                'name' => 'Updated Airport',
                'city' => 'Updated City',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated Airport');

        $this->actingAs($admin)
            ->deleteJson(route('admin.api.airports.destroy', $airportId))
            ->assertOk()
            ->assertJsonPath('message', 'Airport deleted successfully.');
    }
}
