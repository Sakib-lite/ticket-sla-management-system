<?php

namespace Tests\Feature;

use App\Http\Middleware\AddRequestContext;
use App\Models\Category;
use App\Models\Import;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketCreatedNotification;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class HardeningTest extends TestCase
{
    public function test_login_is_throttled_after_five_attempts_per_minute(): void
    {
        foreach (range(1, 5) as $attempt) {
            $this->postJson('/api/login', ['email' => 'nobody@example.test', 'password' => 'wrong'])->assertUnprocessable();
        }

        $this->postJson('/api/login', ['email' => 'nobody@example.test', 'password' => 'wrong'])
            ->assertStatus(429)
            ->assertHeader('Retry-After');
    }

    public function test_api_tokens_expire(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('support-desk')->plainTextToken;

        $this->withToken($token)->getJson('/api/me')->assertOk();

        $this->travel(25)->hours();
        $this->app['auth']->forgetGuards();

        $this->withToken($token)->getJson('/api/me')->assertUnauthorized();
    }

    public function test_expired_tokens_are_pruned_on_a_schedule(): void
    {
        $commands = collect(app(Schedule::class)->events())->map(fn ($event) => $event->command);

        $this->assertTrue($commands->contains(fn (string $command) => str_contains($command, 'sanctum:prune-expired')));
    }

    public function test_unknown_resources_return_a_clean_json_404(): void
    {
        $this->actingAs(User::factory()->agent()->create())->getJson('/api/tickets/999999')
            ->assertNotFound()
            ->assertExactJson(['message' => 'The requested resource was not found.']);

        $this->actingAs(User::factory()->agent()->create())->getJson('/api/nope')
            ->assertNotFound()
            ->assertJsonPath('message', 'The requested resource was not found.');
    }

    public function test_every_api_response_carries_security_and_request_id_headers(): void
    {
        $response = $this->getJson('/api/ping')->assertOk();

        $response->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'no-referrer');
        $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', $response->headers->get(AddRequestContext::HEADER));

        $this->withHeader(AddRequestContext::HEADER, 'trace-123')->getJson('/api/ping')
            ->assertHeader(AddRequestContext::HEADER, 'trace-123');
    }

    public function test_active_categories_are_served_from_cache_until_one_changes(): void
    {
        Category::factory()->create(['name' => 'Hardware']);
        $admin = User::factory()->administrator()->create();
        $this->actingAs($admin);

        $this->getJson('/api/categories')->assertJsonCount(1);
        $this->postJson('/api/categories', ['name' => 'Software'])->assertCreated();
        $this->getJson('/api/categories')->assertJsonCount(2);

        $this->expectsDatabaseQueryCount(0);
        $this->getJson('/api/categories')->assertJsonCount(2);
    }

    public function test_listing_imports_does_not_query_per_row(): void
    {
        $admin = User::factory()->administrator()->create();
        Import::factory()->count(5)->create(['user_id' => $admin->id]);
        $this->actingAs($admin);

        $this->expectsDatabaseQueryCount(3);
        $this->getJson('/api/imports')->assertOk()->assertJsonCount(5, 'data');
    }

    public function test_listing_notifications_does_not_query_per_row(): void
    {
        $admin = User::factory()->administrator()->create();
        $ticket = Ticket::factory()->createdBy($admin)->create();
        Notification::sendNow($admin, new TicketCreatedNotification($ticket));
        Notification::sendNow($admin, new TicketCreatedNotification($ticket));
        $this->actingAs($admin);

        $this->expectsDatabaseQueryCount(3);
        $this->getJson('/api/notifications')->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_listing_agents_is_a_single_query(): void
    {
        $admin = User::factory()->administrator()->create();
        User::factory()->agent()->count(4)->create();
        $this->actingAs($admin);

        $this->expectsDatabaseQueryCount(1);
        $this->getJson('/api/agents')->assertOk()->assertJsonCount(4);
    }
}
