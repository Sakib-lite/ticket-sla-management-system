<?php

namespace Tests\Feature\Notifications;

use App\Listeners\NotifyReplyAdded;
use App\Listeners\NotifyTicketAssigned;
use App\Listeners\NotifyTicketCreated;
use App\Listeners\NotifyTicketResolved;
use App\Models\Category;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\AgentRepliedNotification;
use App\Notifications\CustomerRepliedNotification;
use App\Notifications\TicketAssignedNotification;
use App\Notifications\TicketCreatedNotification;
use App\Notifications\TicketResolvedNotification;
use Database\Seeders\SlaPolicySeeder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TicketNotificationsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->seed(SlaPolicySeeder::class);
    }

    public function test_creating_a_ticket_notifies_the_customer_and_the_auto_assigned_agent(): void
    {
        $customer = User::factory()->create();
        $agent = User::factory()->agent()->create();

        $this->actingAs($customer)->postJson('/api/tickets', $this->payload())->assertCreated();

        Notification::assertSentTo($customer, TicketCreatedNotification::class, fn ($notification, array $channels) => $channels === ['database', 'mail']);
        Notification::assertSentTo($agent, TicketAssignedNotification::class);
        Notification::assertSentTo($customer, TicketAssignedNotification::class);
        Notification::assertCount(3);
    }

    public function test_manual_assignment_notifies_the_agent_and_customer_but_never_the_actor(): void
    {
        $customer = User::factory()->create();
        $admin = User::factory()->administrator()->create();
        $agent = User::factory()->agent()->create();
        $ticket = Ticket::factory()->createdBy($customer)->create();

        $this->actingAs($admin)->postJson("/api/tickets/{$ticket->id}/assign", ['agent_id' => $agent->id])->assertOk();

        Notification::assertSentTo($agent, TicketAssignedNotification::class);
        Notification::assertSentTo($customer, TicketAssignedNotification::class);
        Notification::assertNotSentTo($admin, TicketAssignedNotification::class);
    }

    public function test_claiming_notifies_the_customer_only(): void
    {
        $customer = User::factory()->create();
        $agent = User::factory()->agent()->create();
        $ticket = Ticket::factory()->createdBy($customer)->create();

        $this->actingAs($agent)->postJson("/api/tickets/{$ticket->id}/claim")->assertOk();

        Notification::assertSentTo($customer, TicketAssignedNotification::class);
        Notification::assertNotSentTo($agent, TicketAssignedNotification::class);
    }

    public function test_an_agent_reply_notifies_the_customer(): void
    {
        $customer = User::factory()->create();
        $agent = User::factory()->agent()->create();
        $ticket = Ticket::factory()->createdBy($customer)->assignedTo($agent)->create();

        $this->actingAs($agent)->postJson("/api/tickets/{$ticket->id}/replies", ['body' => 'Try restarting it.'])->assertCreated();

        Notification::assertSentTo($customer, AgentRepliedNotification::class, function (AgentRepliedNotification $notification) use ($customer) {
            return str_contains($notification->toMail($customer)->subject, 'New reply on your ticket')
                && $notification->toArray($customer)['kind'] === 'agent_replied';
        });
        Notification::assertNotSentTo($agent, AgentRepliedNotification::class);
    }

    public function test_a_customer_reply_notifies_the_assignee_or_the_administrators(): void
    {
        $customer = User::factory()->create();
        $agent = User::factory()->agent()->create();
        $admin = User::factory()->administrator()->create();
        $assigned = Ticket::factory()->createdBy($customer)->assignedTo($agent)->create();
        $unassigned = Ticket::factory()->createdBy($customer)->create();

        $this->actingAs($customer)->postJson("/api/tickets/{$assigned->id}/replies", ['body' => 'Still broken.'])->assertCreated();
        $this->actingAs($customer)->postJson("/api/tickets/{$unassigned->id}/replies", ['body' => 'Anyone there?'])->assertCreated();

        Notification::assertSentToTimes($agent, CustomerRepliedNotification::class, 1);
        Notification::assertSentToTimes($admin, CustomerRepliedNotification::class, 1);
        Notification::assertNotSentTo($customer, CustomerRepliedNotification::class);
    }

    public function test_internal_notes_notify_nobody(): void
    {
        $agent = User::factory()->agent()->create();
        $ticket = Ticket::factory()->assignedTo($agent)->create();

        $this->actingAs($agent)->postJson("/api/tickets/{$ticket->id}/replies", ['body' => 'Private', 'is_internal' => true])->assertCreated();

        Notification::assertNothingSent();
    }

    public function test_resolving_notifies_the_customer(): void
    {
        $customer = User::factory()->create();
        $agent = User::factory()->agent()->create();
        $ticket = Ticket::factory()->createdBy($customer)->assignedTo($agent)->create();

        $this->actingAs($agent)->postJson("/api/tickets/{$ticket->id}/status", ['status' => 'resolved'])->assertOk();

        Notification::assertSentTo($customer, TicketResolvedNotification::class);
        Notification::assertNotSentTo($agent, TicketResolvedNotification::class);
    }

    public function test_notifications_and_listeners_run_on_the_queue(): void
    {
        $ticket = Ticket::factory()->create();

        $this->assertInstanceOf(ShouldQueue::class, new TicketCreatedNotification($ticket));

        foreach ([NotifyTicketCreated::class, NotifyTicketAssigned::class, NotifyReplyAdded::class, NotifyTicketResolved::class] as $listener) {
            $this->assertInstanceOf(ShouldQueueAfterCommit::class, app($listener));
        }
    }

    public function test_the_email_links_to_the_ticket_in_the_frontend(): void
    {
        config(['app.frontend_url' => 'https://desk.example.test/']);
        $customer = User::factory()->create(['name' => 'Riley Chen']);
        $ticket = Ticket::factory()->createdBy($customer)->create(['subject' => 'Printer jammed']);

        $mail = (new TicketCreatedNotification($ticket))->toMail($customer);

        $this->assertSame("[{$ticket->ticket_number}] We received your ticket", $mail->subject);
        $this->assertSame('Hello Riley Chen,', $mail->greeting);
        $this->assertSame("https://desk.example.test/dashboard/tickets/{$ticket->id}", $mail->actionUrl);
        $this->assertStringContainsString('Printer jammed', $mail->render());
    }

    /** @return array{subject: string, description: string, priority: string, category_id: int} */
    private function payload(): array
    {
        return [
            'subject' => 'Printer jammed',
            'description' => 'Paper everywhere.',
            'priority' => 'medium',
            'category_id' => Category::factory()->create()->id,
        ];
    }
}
