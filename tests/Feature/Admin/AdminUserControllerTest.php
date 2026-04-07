<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsFlightData;
use Tests\TestCase;

class AdminUserControllerTest extends TestCase
{
    use BuildsFlightData;
    use RefreshDatabase;

    public function test_admin_can_list_create_update_and_delete_users(): void
    {
        $admin = $this->createAdminUser();
        $user = $this->createUser(['name' => 'Search Target']);

        $this->actingAs($admin)
            ->getJson(route('admin.api.users.index', ['search' => 'Search']))
            ->assertOk()
            ->assertJsonFragment(['id' => $user->id, 'name' => 'Search Target']);

        $createdResponse = $this->actingAs($admin)
            ->postJson(route('admin.api.users.store'), [
                'name' => 'Created User',
                'email' => 'created@example.com',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
                'role' => UserRole::User->value,
            ]);

        $createdResponse->assertCreated()
            ->assertJsonPath('data.email', 'created@example.com');

        $createdId = $createdResponse->json('data.id');

        $this->actingAs($admin)
            ->patchJson(route('admin.api.users.update', $createdId), [
                'name' => 'Updated User',
                'role' => UserRole::Admin->value,
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated User');

        $this->actingAs($admin)
            ->deleteJson(route('admin.api.users.destroy', $createdId))
            ->assertOk()
            ->assertJsonPath('message', 'User deleted successfully.');
    }

    public function test_admin_cannot_delete_their_own_account(): void
    {
        $admin = $this->createAdminUser();

        $this->actingAs($admin)
            ->deleteJson(route('admin.api.users.destroy', $admin->id))
            ->assertStatus(422)
            ->assertJsonPath('message', 'You cannot delete your own account.');
    }
}
