<?php

namespace Tests\Feature\Tickets;

use App\Enums\TicketPriority;
use App\Models\Category;
use App\Models\Ticket;
use App\Models\User;
use Tests\TestCase;

class ViewTicketsTest extends TestCase
{
    public function test_customers_only_see_their_own_tickets(): void
    {
        $customer = User::factory()->create();
        $mine = Ticket::factory()->createdBy($customer)->create();
        $theirs = Ticket::factory()->create();

        $this->actingAs($customer)->getJson('/api/tickets')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $mine->id);

        $this->actingAs($customer)->getJson("/api/tickets/{$mine->id}")->assertOk();
        $this->actingAs($customer)->getJson("/api/tickets/{$theirs->id}")->assertForbidden();
    }

    public function test_staff_see_every_ticket(): void
    {
        Ticket::factory()->count(3)->create();

        foreach ([User::factory()->agent()->create(), User::factory()->administrator()->create()] as $staff) {
            $this->actingAs($staff)->getJson('/api/tickets')->assertOk()->assertJsonCount(3, 'data');
        }
    }

    public function test_a_ticket_can_be_fetched_by_its_number(): void
    {
        $ticket = Ticket::factory()->create(['ticket_number' => 'TKT-2026-000042']);

        $this->actingAs(User::factory()->agent()->create())
            ->getJson('/api/tickets/tkt-2026-000042')
            ->assertOk()
            ->assertJsonPath('id', $ticket->id);
    }

    public function test_the_list_is_paginated(): void
    {
        Ticket::factory()->count(7)->create();

        $this->actingAs(User::factory()->agent()->create())
            ->getJson('/api/tickets?per_page=3&page=2')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.last_page', 3)
            ->assertJsonPath('meta.total', 7);
    }

    public function test_active_and_resolved_views_split_the_queue(): void
    {
        Ticket::factory()->create();
        Ticket::factory()->inProgress()->create();
        Ticket::factory()->resolved()->create();
        Ticket::factory()->closed()->create();
        $agent = User::factory()->agent()->create();

        $this->actingAs($agent)->getJson('/api/tickets?view=active')->assertJsonCount(2, 'data');
        $this->actingAs($agent)->getJson('/api/tickets?view=resolved')->assertJsonCount(2, 'data');
        $this->actingAs($agent)->getJson('/api/tickets?view=all')->assertJsonCount(4, 'data');
    }

    public function test_filters_can_be_combined(): void
    {
        $agent = User::factory()->agent()->create();
        $category = Category::factory()->create();
        $match = Ticket::factory()->critical()->assignedTo($agent)->create(['category_id' => $category->id]);
        Ticket::factory()->critical()->assignedTo($agent)->create();
        Ticket::factory()->critical()->create(['category_id' => $category->id]);
        Ticket::factory()->create(['category_id' => $category->id, 'assigned_agent_id' => $agent->id, 'priority' => TicketPriority::Low]);

        $this->actingAs($agent)
            ->getJson("/api/tickets?priority=critical&category_id={$category->id}&assignee={$agent->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $match->id);
    }

    public function test_unassigned_filter_and_sla_filter(): void
    {
        $unassignedBreached = Ticket::factory()->breached()->create();
        Ticket::factory()->breached()->assignedTo(User::factory()->agent()->create())->create();
        Ticket::factory()->create();

        $this->actingAs(User::factory()->agent()->create())
            ->getJson('/api/tickets?assignee=unassigned&sla_status=breached')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $unassignedBreached->id);
    }

    public function test_search_matches_ticket_number_or_subject(): void
    {
        $byNumber = Ticket::factory()->create(['ticket_number' => 'TKT-2026-000777', 'subject' => 'Nothing to see']);
        $bySubject = Ticket::factory()->create(['subject' => 'Printer on floor 3 is jammed']);
        Ticket::factory()->create(['subject' => 'VPN keeps dropping']);
        $agent = User::factory()->agent()->create();

        $this->actingAs($agent)->getJson('/api/tickets?search=000777')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $byNumber->id);
        $this->actingAs($agent)->getJson('/api/tickets?search=printer')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $bySubject->id);
        $this->actingAs($agent)->getJson('/api/tickets?search=%25')->assertJsonCount(0, 'data');
    }

    public function test_date_range_filter(): void
    {
        $old = Ticket::factory()->create(['created_at' => '2026-01-10 10:00:00']);
        $recent = Ticket::factory()->create(['created_at' => '2026-03-15 10:00:00']);

        $this->actingAs(User::factory()->agent()->create())
            ->getJson('/api/tickets?created_from=2026-03-01&created_to=2026-03-31')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $recent->id);

        $this->assertNotSame($old->id, $recent->id);
    }

    public function test_sorting_by_priority_uses_urgency_not_alphabet(): void
    {
        Ticket::factory()->priority(TicketPriority::Medium)->create();
        Ticket::factory()->priority(TicketPriority::Critical)->create();
        Ticket::factory()->priority(TicketPriority::Low)->create();
        Ticket::factory()->priority(TicketPriority::High)->create();

        $response = $this->actingAs(User::factory()->agent()->create())->getJson('/api/tickets?sort=priority&direction=desc');

        $this->assertSame(['critical', 'high', 'medium', 'low'], array_column($response->json('data'), 'priority'));
    }

    public function test_invalid_filters_are_rejected(): void
    {
        $this->actingAs(User::factory()->agent()->create())
            ->getJson('/api/tickets?status=nope&sort=password&per_page=500&assignee=me')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status', 'sort', 'per_page', 'assignee']);
    }

    public function test_listing_a_page_of_tickets_does_not_query_per_row(): void
    {
        Ticket::factory()->count(20)->assignedTo(User::factory()->agent()->create())->create();
        $this->actingAs(User::factory()->agent()->create());

        $this->expectsDatabaseQueryCount(5);

        $this->getJson('/api/tickets')->assertOk()->assertJsonCount(20, 'data');
    }
}
