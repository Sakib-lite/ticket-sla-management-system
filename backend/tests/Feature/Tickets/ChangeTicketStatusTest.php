<?php

namespace Tests\Feature\Tickets;

use App\Enums\ActivityType;
use App\Enums\SlaStatus;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ChangeTicketStatusTest extends TestCase
{
    public function test_an_agent_resolves_a_ticket_and_the_sla_outcome_is_frozen(): void
    {
        $ticket = Ticket::factory()->inProgress()->create();
        $agent = User::factory()->agent()->create();

        $this->actingAs($agent)->postJson("/api/tickets/{$ticket->id}/status", ['status' => 'resolved'])
            ->assertOk()
            ->assertJsonPath('status', 'resolved')
            ->assertJsonPath('sla.resolved_within_sla', true);

        $ticket->refresh();
        $this->assertNotNull($ticket->resolved_at);
        $this->assertSame(SlaStatus::Within, $ticket->sla_status);
        $this->assertDatabaseHas('ticket_activities', [
            'ticket_id' => $ticket->id,
            'type' => ActivityType::StatusChanged->value,
            'actor_id' => $agent->id,
        ]);
    }

    public function test_resolving_after_the_deadline_records_a_breach(): void
    {
        $ticket = Ticket::factory()->breached()->create();

        $this->actingAs(User::factory()->agent()->create())
            ->postJson("/api/tickets/{$ticket->id}/status", ['status' => 'resolved'])
            ->assertOk()
            ->assertJsonPath('sla.resolved_within_sla', false)
            ->assertJsonPath('sla.status', 'breached');
    }

    public function test_closing_an_open_ticket_also_marks_it_resolved(): void
    {
        $ticket = Ticket::factory()->create();

        $this->actingAs(User::factory()->agent()->create())
            ->postJson("/api/tickets/{$ticket->id}/status", ['status' => 'closed'])
            ->assertOk();

        $ticket->refresh();
        $this->assertNotNull($ticket->resolved_at);
        $this->assertNotNull($ticket->closed_at);
    }

    public function test_reopening_clears_the_resolution(): void
    {
        $ticket = Ticket::factory()->resolved()->create();

        $this->actingAs(User::factory()->agent()->create())
            ->postJson("/api/tickets/{$ticket->id}/status", ['status' => 'open'])
            ->assertOk()
            ->assertJsonPath('status', 'open')
            ->assertJsonPath('sla.resolved_within_sla', null);

        $this->assertNull($ticket->refresh()->resolved_at);
    }

    /** @return array<string, array{TicketStatus, string}> */
    public static function forbiddenTransitions(): array
    {
        return [
            'closed tickets are final' => [TicketStatus::Closed, 'open'],
            'resolved cannot go back to in progress' => [TicketStatus::Resolved, 'in_progress'],
            'a status cannot be set to itself' => [TicketStatus::Open, 'open'],
        ];
    }

    #[DataProvider('forbiddenTransitions')]
    public function test_invalid_transitions_are_rejected(TicketStatus $from, string $to): void
    {
        $ticket = Ticket::factory()->status($from)->create();

        $this->actingAs(User::factory()->agent()->create())
            ->postJson("/api/tickets/{$ticket->id}/status", ['status' => $to])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');

        $this->assertSame($from, $ticket->refresh()->status);
    }

    public function test_customers_cannot_change_status(): void
    {
        $customer = User::factory()->create();
        $ticket = Ticket::factory()->createdBy($customer)->create();

        $this->actingAs($customer)
            ->postJson("/api/tickets/{$ticket->id}/status", ['status' => 'resolved'])
            ->assertForbidden();
    }

    public function test_unknown_statuses_are_rejected(): void
    {
        $ticket = Ticket::factory()->create();

        $this->actingAs(User::factory()->agent()->create())
            ->postJson("/api/tickets/{$ticket->id}/status", ['status' => 'done'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');
    }
}
