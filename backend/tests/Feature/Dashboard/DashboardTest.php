<?php

namespace Tests\Feature\Dashboard;

use App\Enums\TicketPriority;
use App\Models\Category;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\SlaPolicySeeder;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    public function test_staff_see_headline_numbers_for_every_ticket(): void
    {
        $jordan = User::factory()->agent()->create(['name' => 'Jordan Reed']);
        $sam = User::factory()->agent()->create(['name' => 'Sam Okafor']);
        Ticket::factory()->count(2)->priority(TicketPriority::Low)->create();
        Ticket::factory()->critical()->assignedTo($jordan)->create();
        Ticket::factory()->priority(TicketPriority::High)->breached()->assignedTo($jordan)->create();
        Ticket::factory()->priority(TicketPriority::Medium)->approachingSla()->assignedTo($sam)->create();
        Ticket::factory()->priority(TicketPriority::Low)->resolved()->create(['resolved_at' => now()->subDays(2)]);
        Ticket::factory()->priority(TicketPriority::Low)->closed()->create(['resolved_at' => now()->subDays(20)]);

        $response = $this->actingAs(User::factory()->administrator()->create())->getJson('/api/dashboard')->assertOk();

        $response->assertJsonPath('metrics', [
            'open' => 5,
            'unassigned' => 2,
            'critical' => 1,
            'breached' => 1,
            'approaching' => 1,
            'resolved' => 2,
            'resolved_last_7_days' => 1,
        ]);
        $this->assertSame(['critical', 'high', 'medium', 'low'], array_column($response->json('by_priority'), 'priority'));
        $this->assertSame(1, collect($response->json('by_priority'))->firstWhere('priority', 'critical')['total']);
        $this->assertSame([
            ['agent_id' => $jordan->id, 'agent_name' => 'Jordan Reed', 'total' => 2, 'breached' => 1],
            ['agent_id' => $sam->id, 'agent_name' => 'Sam Okafor', 'total' => 1, 'breached' => 0],
        ], $response->json('by_agent'));
    }

    public function test_customers_only_see_their_own_numbers_and_no_agent_breakdown(): void
    {
        $customer = User::factory()->create();
        Ticket::factory()->createdBy($customer)->create();
        Ticket::factory()->createdBy($customer)->resolved()->create();
        Ticket::factory()->count(3)->create();

        $response = $this->actingAs($customer)->getJson('/api/dashboard')->assertOk();

        $response->assertJsonPath('metrics.open', 1)
            ->assertJsonPath('metrics.resolved', 1)
            ->assertJsonMissingPath('by_agent');
    }

    public function test_numbers_are_cached_but_refresh_when_a_ticket_changes(): void
    {
        $this->seed(SlaPolicySeeder::class);
        $agent = User::factory()->agent()->create();
        Ticket::factory()->create();

        $this->actingAs($agent)->getJson('/api/dashboard')->assertJsonPath('metrics.open', 1);

        Ticket::factory()->create();
        $this->actingAs($agent)->getJson('/api/dashboard')->assertJsonPath('metrics.open', 2);

        $this->actingAs(User::factory()->create())->postJson('/api/tickets', [
            'subject' => 'Third',
            'description' => 'Through the API',
            'priority' => TicketPriority::Low->value,
            'category_id' => Category::factory()->create()->id,
        ])->assertCreated();
        $this->actingAs($agent)->getJson('/api/dashboard')->assertJsonPath('metrics.open', 3);
    }

    public function test_the_dashboard_needs_three_queries_and_then_none(): void
    {
        Ticket::factory()->count(10)->assignedTo(User::factory()->agent()->create())->create();
        $admin = User::factory()->administrator()->create();
        $this->actingAs($admin);

        $this->expectsDatabaseQueryCount(3);
        $this->getJson('/api/dashboard')->assertOk();

        $this->expectsDatabaseQueryCount(0);
        $this->getJson('/api/dashboard')->assertOk();
    }

    public function test_guests_are_rejected(): void
    {
        $this->getJson('/api/dashboard')->assertUnauthorized();
    }
}
