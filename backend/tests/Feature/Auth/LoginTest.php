<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Tests\TestCase;

class LoginTest extends TestCase
{
    public function test_a_user_receives_a_token_with_valid_credentials(): void
    {
        $user = User::factory()->agent()->create(['email' => 'agent@example.test']);

        $response = $this->postJson('/api/login', [
            'email' => 'agent@example.test',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.role', 'agent')
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email', 'role']]);
    }

    public function test_a_user_is_rejected_with_a_wrong_password(): void
    {
        User::factory()->create(['email' => 'customer@example.test']);

        $this->postJson('/api/login', [
            'email' => 'customer@example.test',
            'password' => 'not-the-password',
        ])->assertStatus(422)->assertJsonValidationErrors('email');
    }

    public function test_the_current_user_is_only_readable_with_a_token(): void
    {
        $this->getJson('/api/me')->assertUnauthorized();
    }

    public function test_logging_out_revokes_the_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('testing')->plainTextToken;

        $this->withToken($token)->postJson('/api/logout')->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);

        $this->app['auth']->forgetGuards();

        $this->withToken($token)->getJson('/api/me')->assertUnauthorized();
    }
}
