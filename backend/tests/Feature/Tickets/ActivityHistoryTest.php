<?php

namespace Tests\Feature\Tickets;

use App\Enums\ActivityType;
use App\Models\Category;
use App\Models\Ticket;
use App\Models\TicketActivity;
use App\Models\User;
use Database\Seeders\SlaPolicySeeder;
use Tests\TestCase;

class ActivityHistoryTest extends TestCase
{
    public function test_the_full_story_of_a_ticket_is_traceable(): void
    {
        $this->seed(SlaPolicySeeder::class);
        $customer = User::factory()->create(['name' => 'Riley Chen']);
        $jordan = User::factory()->agent()->create(['name' => 'Jordan Reed']);
        $sam = User::factory()->agent()->create(['name' => 'Sam Okafor']);
        $admin = User::factory()->administrator()->create(['name' => 'Avery Blake']);
        $category = Category::factory()->create();

        $ticketId = $this->actingAs($customer)->postJson('/api/tickets', [
            'subject' => 'Printer jammed',
            'description' => 'Again.',
            'priority' => 'medium',
            'category_id' => $category->id,
        ])->json('id');
        $this->actingAs($admin)->postJson("/api/tickets/{$ticketId}/assign", ['agent_id' => $sam->id]);
        $this->actingAs($admin)->patchJson("/api/tickets/{$ticketId}", ['priority' => 'high']);
        $this->actingAs($sam)->postJson("/api/tickets/{$ticketId}/replies", ['body' => 'On it.']);
        $this->actingAs($sam)->postJson("/api/tickets/{$ticketId}/status", ['status' => 'resolved']);

        $response = $this->actingAs($jordan)->getJson("/api/tickets/{$ticketId}/activities")->assertOk();

        $this->assertSame([
            'Riley Chen created the ticket',
            'Automatically assigned to Jordan Reed',
            'Avery Blake reassigned the ticket from Jordan Reed to Sam Okafor',
            'Avery Blake changed the priority from Medium to High',
            'Sam Okafor replied',
            'Sam Okafor changed the status from Open to Waiting for Customer',
            'Sam Okafor changed the status from Waiting for Customer to Resolved',
        ], array_reverse(array_column($response->json('data'), 'description')));
    }

    public function test_history_is_paginated_newest_first(): void
    {
        $ticket = Ticket::factory()->create();
        foreach (range(1, 30) as $minute) {
            TicketActivity::factory()->for($ticket)->create(['created_at' => now()->subMinutes(60 - $minute)]);
        }

        $response = $this->actingAs(User::factory()->agent()->create())
            ->getJson("/api/tickets/{$ticket->id}/activities?per_page=10&page=2")
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.total', 30)
            ->assertJsonPath('meta.last_page', 3);

        $timestamps = array_column($response->json('data'), 'created_at');
        $this->assertSame($timestamps, array_reverse((array) collect($timestamps)->sort()->values()->all()));
    }

    public function test_customers_do_not_see_internal_note_activities(): void
    {
        $customer = User::factory()->create();
        $ticket = Ticket::factory()->createdBy($customer)->create();
        TicketActivity::factory()->for($ticket)->type(ActivityType::ReplyAdded, ['reply_id' => 1])->create();
        TicketActivity::factory()->for($ticket)->type(ActivityType::NoteAdded, ['reply_id' => 2])->create();

        $this->actingAs($customer)->getJson("/api/tickets/{$ticket->id}/activities")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', 'reply_added');
    }

    public function test_history_is_protected_like_the_ticket(): void
    {
        $ticket = Ticket::factory()->create();

        $this->getJson("/api/tickets/{$ticket->id}/activities")->assertUnauthorized();
        $this->actingAs(User::factory()->create())->getJson("/api/tickets/{$ticket->id}/activities")->assertForbidden();
    }

    public function test_listing_history_does_not_query_per_row(): void
    {
        $ticket = Ticket::factory()->create();
        TicketActivity::factory()->count(15)->for($ticket)->create();
        $this->actingAs(User::factory()->agent()->create());

        $this->expectsDatabaseQueryCount(4);

        $this->getJson("/api/tickets/{$ticket->id}/activities")->assertOk()->assertJsonCount(15, 'data');
    }
}
