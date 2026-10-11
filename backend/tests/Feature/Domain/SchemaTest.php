<?php

namespace Tests\Feature\Domain;

use App\Enums\ActivityType;
use App\Enums\TicketPriority;
use App\Models\Attachment;
use App\Models\Category;
use App\Models\SlaPolicy;
use App\Models\Ticket;
use App\Models\TicketActivity;
use App\Models\TicketReply;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\QueryException;
use Tests\TestCase;

class SchemaTest extends TestCase
{
    public function test_the_database_seeder_builds_a_consistent_demo_dataset(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(4, SlaPolicy::count());
        $this->assertSame(2, SlaPolicy::where('priority', TicketPriority::Critical)->value('resolution_hours'));
        $this->assertSame(6, Category::count());
        $this->assertGreaterThanOrEqual(15, Ticket::count());

        Ticket::with(['category', 'creator', 'assignee'])->get()->each(function (Ticket $ticket) {
            $this->assertNotNull($ticket->category);
            $this->assertTrue($ticket->creator->isCustomer());
            $this->assertTrue($ticket->assignee?->isAgent() ?? true);
            $this->assertMatchesRegularExpression('/^TKT-\d{4}-\d{6,}$/', $ticket->ticket_number);
        });

        $this->assertDatabaseHas('ticket_sequences', ['year' => now()->year, 'last_number' => Ticket::count()]);
    }

    public function test_ticket_numbers_are_unique_at_the_database_level(): void
    {
        Ticket::factory()->create(['ticket_number' => 'TKT-2026-000001']);

        $this->expectException(QueryException::class);
        Ticket::factory()->create(['ticket_number' => 'TKT-2026-000001']);
    }

    public function test_relationships_resolve_in_both_directions(): void
    {
        $customer = User::factory()->create();
        $agent = User::factory()->agent()->create();
        $ticket = Ticket::factory()->createdBy($customer)->assignedTo($agent)->create();
        $reply = TicketReply::factory()->for($ticket)->by($agent)->create();
        Attachment::factory()->for($ticket, 'attachable')->create();
        Attachment::factory()->for($reply, 'attachable')->create();
        TicketActivity::factory()->for($ticket)->type(ActivityType::Assigned)->create();

        $this->assertTrue($customer->tickets->contains($ticket));
        $this->assertTrue($agent->assignedTickets->contains($ticket));
        $this->assertTrue($ticket->replies->contains($reply));
        $this->assertCount(1, $ticket->attachments);
        $this->assertCount(1, $reply->attachments);
        $this->assertCount(1, $ticket->activities);
        $this->assertTrue($reply->attachments->first()->attachable->is($reply));
    }

    public function test_customers_cannot_see_internal_notes_or_note_activities(): void
    {
        $customer = User::factory()->create();
        $agent = User::factory()->agent()->create();
        $ticket = Ticket::factory()->createdBy($customer)->create();
        TicketReply::factory()->for($ticket)->by($agent)->create();
        TicketReply::factory()->for($ticket)->by($agent)->internal()->create();
        TicketActivity::factory()->for($ticket)->type(ActivityType::ReplyAdded)->create();
        TicketActivity::factory()->for($ticket)->type(ActivityType::NoteAdded)->create();

        $this->assertSame(1, $ticket->replies()->visibleTo($customer)->count());
        $this->assertSame(2, $ticket->replies()->visibleTo($agent)->count());
        $this->assertSame(1, $ticket->activities()->visibleTo($customer)->count());
        $this->assertSame(2, $ticket->activities()->visibleTo($agent)->count());
    }

    public function test_ticket_scopes_split_active_and_finished_work(): void
    {
        Ticket::factory()->create();
        Ticket::factory()->inProgress()->create();
        Ticket::factory()->resolved()->create();
        Ticket::factory()->closed()->create();

        $this->assertSame(2, Ticket::active()->count());
        $this->assertSame(2, Ticket::finished()->count());
        $this->assertSame(4, Ticket::unassigned()->count());
    }

    public function test_deleting_a_ticket_removes_its_replies_and_activities(): void
    {
        $ticket = Ticket::factory()->create();
        TicketReply::factory()->for($ticket)->create();
        TicketActivity::factory()->for($ticket)->create();

        $ticket->delete();

        $this->assertDatabaseCount('ticket_replies', 0);
        $this->assertDatabaseCount('ticket_activities', 0);
    }
}
