<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsFlightData;
use Tests\TestCase;

class AdminDashboardControllerTest extends TestCase
{
    use BuildsFlightData;
    use RefreshDatabase;

    public function test_admin_can_view_the_dashboard_page(): void
    {
        $admin = $this->createAdminUser();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk();
    }

    public function test_admin_can_fetch_dashboard_stats(): void
    {
        $admin = $this->createAdminUser();
        $this->createUser();
        $this->createAirline();
        $this->createAirport();
        $this->createFlight();
        $this->createTrip();

        $this->actingAs($admin)
            ->getJson(route('admin.api.dashboard.stats'))
            ->assertOk()
            ->assertJsonPath('users', 3)
            ->assertJsonPath('airlines', 2)
            ->assertJsonPath('airports', 3)
            ->assertJsonPath('flights', 1)
            ->assertJsonPath('trips', 1);
    }
}
