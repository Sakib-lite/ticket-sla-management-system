<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    public function test_administrators_list_users_with_role_and_search_filters(): void
    {
        $admin = User::factory()->administrator()->create(['name' => 'Avery Blake']);
        User::factory()->agent()->create(['name' => 'Jordan Reed', 'email' => 'jordan@example.test']);
        User::factory()->agent()->create(['name' => 'Sam Okafor']);
        User::factory()->create(['name' => 'Riley Chen']);

        $this->actingAs($admin)->getJson('/api/users')->assertOk()->assertJsonCount(4, 'data')->assertJsonPath('data.0.name', 'Avery Blake');
        $this->actingAs($admin)->getJson('/api/users?role=agent')->assertJsonCount(2, 'data');
        $this->actingAs($admin)->getJson('/api/users?search=jordan@')->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Jordan Reed');
    }

    public function test_only_administrators_manage_users(): void
    {
        $agent = User::factory()->agent()->create();
        $customer = User::factory()->create();

        $this->actingAs($agent)->getJson('/api/users')->assertForbidden();
        $this->actingAs($agent)->postJson('/api/users', [])->assertForbidden();
        $this->actingAs($customer)->patchJson("/api/users/{$agent->id}", ['name' => 'x'])->assertForbidden();
    }

    public function test_an_administrator_creates_an_agent_account(): void
    {
        $admin = User::factory()->administrator()->create();

        $response = $this->actingAs($admin)->postJson('/api/users', [
            'name' => 'Priya Nair',
            'email' => 'priya@example.test',
            'password' => 'correct horse battery',
            'role' => 'agent',
        ]);

        $response->assertCreated()->assertJsonPath('role', 'agent')->assertJsonPath('is_available', true)->assertJsonMissingPath('password');
        $user = User::where('email', 'priya@example.test')->firstOrFail();
        $this->assertSame(Role::Agent, $user->role);
        $this->assertTrue(Hash::check('correct horse battery', $user->password));
    }

    public function test_user_creation_is_validated(): void
    {
        $admin = User::factory()->administrator()->create();
        User::factory()->create(['email' => 'taken@example.test']);

        $this->actingAs($admin)->postJson('/api/users', [
            'name' => '',
            'email' => 'taken@example.test',
            'password' => 'short',
            'role' => 'owner',
        ])->assertUnprocessable()->assertJsonValidationErrors(['name', 'email', 'password', 'role']);
    }

    public function test_an_administrator_changes_a_role_and_availability(): void
    {
        $admin = User::factory()->administrator()->create();
        $user = User::factory()->create();

        $this->actingAs($admin)->patchJson("/api/users/{$user->id}", ['role' => 'agent', 'is_available' => false])
            ->assertOk()
            ->assertJsonPath('role', 'agent')
            ->assertJsonPath('is_available', false);
    }

    public function test_an_administrator_cannot_change_their_own_role(): void
    {
        $admin = User::factory()->administrator()->create();

        $this->actingAs($admin)->patchJson("/api/users/{$admin->id}", ['role' => 'customer'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('role');

        $this->assertSame(Role::Administrator, $admin->refresh()->role);
    }

    public function test_agents_toggle_their_own_availability(): void
    {
        $agent = User::factory()->agent()->create();

        $this->actingAs($agent)->patchJson('/api/me/availability', ['is_available' => false])
            ->assertOk()
            ->assertJsonPath('is_available', false);

        $this->assertFalse($agent->refresh()->is_available);
        $this->actingAs($agent)->getJson('/api/agents')->assertJsonPath('0.is_available', false);
    }

    public function test_customers_have_no_availability_to_toggle(): void
    {
        $this->actingAs(User::factory()->create())->patchJson('/api/me/availability', ['is_available' => false])->assertForbidden();
    }
}
