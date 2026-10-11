<?php

namespace Tests\Feature\Tickets;

use App\Enums\ActivityType;
use App\Models\Category;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\SlaPolicySeeder;
use Tests\TestCase;

class AssignmentTest extends TestCase
{
    public function test_an_administrator_assigns_a_ticket_to_an_agent(): void
    {
        $admin = User::factory()->administrator()->create();
        $agent = User::factory()->agent()->create();
        $ticket = Ticket::factory()->create();

        $this->actingAs($admin)->postJson("/api/tickets/{$ticket->id}/assign", ['agent_id' => $agent->id])
            ->assertOk()
            ->assertJsonPath('assignee.id', $agent->id);

        $this->assertNotNull($agent->refresh()->last_assigned_at);
        $this->assertDatabaseHas('ticket_activities', [
            'ticket_id' => $ticket->id,
            'type' => ActivityType::Assigned->value,
            'actor_id' => $admin->id,
        ]);
    }

    public function test_reassignment_records_the_previous_agent(): void
    {
        $from = User::factory()->agent()->create(['name' => 'Jordan']);
        $to = User::factory()->agent()->create(['name' => 'Sam']);
        $ticket = Ticket::factory()->assignedTo($from)->create();

        $this->actingAs(User::factory()->agent()->create())
            ->postJson("/api/tickets/{$ticket->id}/assign", ['agent_id' => $to->id])
            ->assertOk();

        $activity = $ticket->activities()->where('type', ActivityType::Assigned)->firstOrFail();
        $this->assertSame(['from' => 'Jordan', 'to' => 'Sam', 'agent_id' => $to->id], $activity->payload);
    }

    public function test_only_agents_can_be_assigned(): void
    {
        $customer = User::factory()->create();
        $ticket = Ticket::factory()->create();

        $this->actingAs(User::factory()->administrator()->create())
            ->postJson("/api/tickets/{$ticket->id}/assign", ['agent_id' => $customer->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('agent_id');
    }

    public function test_customers_cannot_assign_claim_or_list_agents(): void
    {
        $customer = User::factory()->create();
        $ticket = Ticket::factory()->createdBy($customer)->create();
        $agent = User::factory()->agent()->create();

        $this->actingAs($customer)->postJson("/api/tickets/{$ticket->id}/assign", ['agent_id' => $agent->id])->assertForbidden();
        $this->actingAs($customer)->postJson("/api/tickets/{$ticket->id}/claim")->assertForbidden();
        $this->actingAs($customer)->getJson('/api/agents')->assertForbidden();
    }

    public function test_an_agent_claims_an_unassigned_ticket(): void
    {
        $agent = User::factory()->agent()->create();
        $ticket = Ticket::factory()->create();

        $this->actingAs($agent)->postJson("/api/tickets/{$ticket->id}/claim")
            ->assertOk()
            ->assertJsonPath('assignee.id', $agent->id);

        $this->assertDatabaseHas('ticket_activities', [
            'ticket_id' => $ticket->id,
            'type' => ActivityType::Claimed->value,
            'actor_id' => $agent->id,
        ]);
    }

    public function test_claiming_a_taken_ticket_conflicts_and_names_the_assignee(): void
    {
        $owner = User::factory()->agent()->create(['name' => 'Jordan Reed']);
        $ticket = Ticket::factory()->assignedTo($owner)->create();

        $this->actingAs(User::factory()->agent()->create())
            ->postJson("/api/tickets/{$ticket->id}/claim")
            ->assertStatus(409)
            ->assertJsonPath('assignee.id', $owner->id)
            ->assertJsonPath('message', "{$ticket->ticket_number} was just taken by Jordan Reed.");

        $this->assertSame($owner->id, $ticket->refresh()->assigned_agent_id);
    }

    public function test_staff_can_unassign_a_ticket(): void
    {
        $agent = User::factory()->agent()->create(['name' => 'Jordan']);
        $ticket = Ticket::factory()->assignedTo($agent)->create();

        $this->actingAs(User::factory()->administrator()->create())
            ->deleteJson("/api/tickets/{$ticket->id}/assignment")
            ->assertOk()
            ->assertJsonPath('assignee', null);

        $activity = $ticket->activities()->where('type', ActivityType::Unassigned)->firstOrFail();
        $this->assertSame(['from' => 'Jordan'], $activity->payload);
    }

    public function test_agents_are_listed_with_their_active_workload(): void
    {
        $busy = User::factory()->agent()->create(['name' => 'Busy']);
        $free = User::factory()->agent()->unavailable()->create(['name' => 'Away']);
        Ticket::factory()->count(2)->assignedTo($busy)->create();
        Ticket::factory()->resolved()->create(['assigned_agent_id' => $busy->id]);
        User::factory()->create();

        $response = $this->actingAs(User::factory()->agent()->create(['name' => 'Zed']))
            ->getJson('/api/agents')
            ->assertOk()
            ->assertJsonCount(3);

        $this->assertSame(['Away', 'Busy', 'Zed'], array_column($response->json(), 'name'));
        $this->assertSame(2, $response->json('1.active_tickets_count'));
        $this->assertFalse($response->json('0.is_available'));
        $this->assertSame($free->id, $response->json('0.id'));
    }

    public function test_new_tickets_go_to_the_least_loaded_available_agent(): void
    {
        $this->seed(SlaPolicySeeder::class);
        $busy = User::factory()->agent()->create(['last_assigned_at' => now()->subHour()]);
        $idle = User::factory()->agent()->create(['last_assigned_at' => now()->subDay()]);
        $away = User::factory()->agent()->unavailable()->create();
        Ticket::factory()->assignedTo($busy)->create();

        $response = $this->actingAs(User::factory()->create())->postJson('/api/tickets', [
            'subject' => 'Auto-assigned',
            'description' => 'Should land with the idle agent.',
            'priority' => 'medium',
            'category_id' => Category::factory()->create()->id,
        ]);

        $response->assertCreated()->assertJsonPath('assignee.id', $idle->id);
        $this->assertNotSame($away->id, $response->json('assignee.id'));
        $this->assertDatabaseHas('ticket_activities', [
            'ticket_id' => $response->json('id'),
            'type' => ActivityType::AutoAssigned->value,
            'actor_id' => null,
        ]);
    }

    public function test_ties_rotate_to_the_agent_assigned_longest_ago(): void
    {
        $this->seed(SlaPolicySeeder::class);
        $recent = User::factory()->agent()->create(['last_assigned_at' => now()->subMinute()]);
        $stale = User::factory()->agent()->create(['last_assigned_at' => now()->subDays(3)]);
        $never = User::factory()->agent()->create(['last_assigned_at' => null]);
        $payload = ['subject' => 'S', 'description' => 'D', 'priority' => 'low', 'category_id' => Category::factory()->create()->id];
        $customer = User::factory()->create();

        $first = $this->actingAs($customer)->postJson('/api/tickets', $payload)->json('assignee.id');
        $second = $this->actingAs($customer)->postJson('/api/tickets', $payload)->json('assignee.id');
        $third = $this->actingAs($customer)->postJson('/api/tickets', $payload)->json('assignee.id');

        $this->assertSame([$never->id, $stale->id, $recent->id], [$first, $second, $third]);
    }

    public function test_tickets_stay_unassigned_when_no_agent_is_available(): void
    {
        $this->seed(SlaPolicySeeder::class);
        User::factory()->agent()->unavailable()->create();

        $this->actingAs(User::factory()->create())
            ->postJson('/api/tickets', $this->ticketPayload())
            ->assertCreated()
            ->assertJsonPath('assignee', null);
    }

    public function test_auto_assignment_can_be_switched_off(): void
    {
        $this->seed(SlaPolicySeeder::class);
        config(['tickets.auto_assign' => false]);
        User::factory()->agent()->create();

        $this->actingAs(User::factory()->create())
            ->postJson('/api/tickets', $this->ticketPayload())
            ->assertCreated()
            ->assertJsonPath('assignee', null);
    }

    /** @return array{subject: string, description: string, priority: string, category_id: int} */
    private function ticketPayload(): array
    {
        return [
            'subject' => 'Needs an owner',
            'description' => 'Whoever is free.',
            'priority' => 'low',
            'category_id' => Category::factory()->create()->id,
        ];
    }
}
