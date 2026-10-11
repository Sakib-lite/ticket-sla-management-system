<?php

namespace Tests\Feature\Tickets;

use App\Enums\ActivityType;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\User;
use Tests\TestCase;

class ReplyTest extends TestCase
{
    public function test_a_customer_replies_to_their_own_ticket(): void
    {
        $customer = User::factory()->create();
        $ticket = Ticket::factory()->createdBy($customer)->create();

        $this->actingAs($customer)->postJson("/api/tickets/{$ticket->id}/replies", ['body' => 'Still broken.'])
            ->assertCreated()
            ->assertJsonPath('body', 'Still broken.')
            ->assertJsonPath('is_internal', false)
            ->assertJsonPath('author.id', $customer->id);

        $this->assertDatabaseHas('ticket_activities', [
            'ticket_id' => $ticket->id,
            'type' => ActivityType::ReplyAdded->value,
            'actor_id' => $customer->id,
        ]);
    }

    public function test_an_agent_reply_hands_the_ticket_back_to_the_customer(): void
    {
        $agent = User::factory()->agent()->create();
        $ticket = Ticket::factory()->assignedTo($agent)->create();

        $this->actingAs($agent)->postJson("/api/tickets/{$ticket->id}/replies", ['body' => 'Please restart it.'])
            ->assertCreated();

        $this->assertSame(TicketStatus::WaitingForCustomer, $ticket->refresh()->status);
        $this->assertDatabaseHas('ticket_activities', [
            'ticket_id' => $ticket->id,
            'type' => ActivityType::StatusChanged->value,
            'actor_id' => $agent->id,
        ]);
    }

    public function test_a_customer_reply_puts_a_waiting_ticket_back_in_the_queue(): void
    {
        $customer = User::factory()->create();
        $ticket = Ticket::factory()->createdBy($customer)->status(TicketStatus::WaitingForCustomer)->create();

        $this->actingAs($customer)->postJson("/api/tickets/{$ticket->id}/replies", ['body' => 'Done, no change.'])
            ->assertCreated();

        $this->assertSame(TicketStatus::Open, $ticket->refresh()->status);
    }

    public function test_a_customer_reply_reopens_a_resolved_ticket(): void
    {
        $customer = User::factory()->create();
        $ticket = Ticket::factory()->createdBy($customer)->resolved()->create();

        $this->actingAs($customer)->postJson("/api/tickets/{$ticket->id}/replies", ['body' => 'It came back.'])
            ->assertCreated();

        $ticket->refresh();
        $this->assertSame(TicketStatus::Open, $ticket->status);
        $this->assertNull($ticket->resolved_at);
    }

    public function test_internal_notes_never_reach_customers(): void
    {
        $customer = User::factory()->create();
        $agent = User::factory()->agent()->create();
        $ticket = Ticket::factory()->createdBy($customer)->create();

        $this->actingAs($agent)->postJson("/api/tickets/{$ticket->id}/replies", [
            'body' => 'Customer has the old driver, check with IT.',
            'is_internal' => true,
        ])->assertCreated()->assertJsonPath('is_internal', true);
        $this->actingAs($agent)->postJson("/api/tickets/{$ticket->id}/replies", ['body' => 'Looking into it.'])->assertCreated();

        $customerView = $this->actingAs($customer)->getJson("/api/tickets/{$ticket->id}/replies")->assertOk();
        $this->assertSame(['Looking into it.'], array_column($customerView->json(), 'body'));

        $this->actingAs($agent)->getJson("/api/tickets/{$ticket->id}/replies")->assertOk()->assertJsonCount(2);
        $this->assertSame(1, $ticket->activities()->where('type', ActivityType::NoteAdded)->count());
    }

    public function test_an_internal_note_does_not_change_the_ticket_status(): void
    {
        $agent = User::factory()->agent()->create();
        $ticket = Ticket::factory()->inProgress()->create();

        $this->actingAs($agent)->postJson("/api/tickets/{$ticket->id}/replies", ['body' => 'Note', 'is_internal' => true]);

        $this->assertSame(TicketStatus::InProgress, $ticket->refresh()->status);
    }

    public function test_customers_cannot_write_internal_notes(): void
    {
        $customer = User::factory()->create();
        $ticket = Ticket::factory()->createdBy($customer)->create();

        $this->actingAs($customer)->postJson("/api/tickets/{$ticket->id}/replies", ['body' => 'x', 'is_internal' => true])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('is_internal');
    }

    public function test_customers_cannot_read_or_reply_to_other_peoples_tickets(): void
    {
        $customer = User::factory()->create();
        $ticket = Ticket::factory()->create();

        $this->actingAs($customer)->getJson("/api/tickets/{$ticket->id}/replies")->assertForbidden();
        $this->actingAs($customer)->postJson("/api/tickets/{$ticket->id}/replies", ['body' => 'Hi'])->assertForbidden();
    }

    public function test_closed_tickets_do_not_accept_replies(): void
    {
        $ticket = Ticket::factory()->closed()->create();

        $this->actingAs(User::factory()->agent()->create())
            ->postJson("/api/tickets/{$ticket->id}/replies", ['body' => 'Too late'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('body');

        $this->assertDatabaseCount('ticket_replies', 0);
    }

    public function test_replies_are_returned_in_order_with_their_authors(): void
    {
        $ticket = Ticket::factory()->create();
        $first = TicketReply::factory()->for($ticket)->create(['created_at' => now()->subMinutes(10)]);
        $second = TicketReply::factory()->for($ticket)->create(['created_at' => now()->subMinutes(5)]);
        $this->actingAs(User::factory()->agent()->create());

        $this->expectsDatabaseQueryCount(3);

        $response = $this->getJson("/api/tickets/{$ticket->id}/replies")->assertOk();

        $this->assertSame([$first->id, $second->id], array_column($response->json(), 'id'));
        $this->assertNotNull($response->json('0.author.name'));
    }
}
