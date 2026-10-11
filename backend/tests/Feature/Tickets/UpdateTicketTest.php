<?php

namespace Tests\Feature\Tickets;

use App\Enums\ActivityType;
use App\Enums\SlaStatus;
use App\Enums\TicketPriority;
use App\Models\Category;
use App\Models\Ticket;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\SlaPolicySeeder;
use Tests\TestCase;

class UpdateTicketTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SlaPolicySeeder::class);
    }

    public function test_a_customer_can_edit_the_subject_and_description_of_their_open_ticket(): void
    {
        $customer = User::factory()->create();
        $ticket = Ticket::factory()->createdBy($customer)->create(['subject' => 'Old subject']);

        $this->actingAs($customer)->patchJson("/api/tickets/{$ticket->id}", [
            'subject' => 'New subject',
            'description' => 'More detail',
        ])->assertOk()->assertJsonPath('subject', 'New subject');

        $this->assertDatabaseHas('ticket_activities', [
            'ticket_id' => $ticket->id,
            'type' => ActivityType::SubjectChanged->value,
            'actor_id' => $customer->id,
        ]);
    }

    public function test_a_customer_cannot_change_priority_or_category(): void
    {
        $customer = User::factory()->create();
        $ticket = Ticket::factory()->createdBy($customer)->create();

        $this->actingAs($customer)->patchJson("/api/tickets/{$ticket->id}", [
            'priority' => 'critical',
            'category_id' => Category::factory()->create()->id,
        ])->assertUnprocessable()->assertJsonValidationErrors(['priority', 'category_id']);
    }

    public function test_a_customer_cannot_edit_someone_elses_ticket_or_a_resolved_one(): void
    {
        $customer = User::factory()->create();
        $theirs = Ticket::factory()->create();
        $resolved = Ticket::factory()->createdBy($customer)->resolved()->create();

        $this->actingAs($customer)->patchJson("/api/tickets/{$theirs->id}", ['subject' => 'x'])->assertForbidden();
        $this->actingAs($customer)->patchJson("/api/tickets/{$resolved->id}", ['subject' => 'x'])->assertForbidden();
    }

    public function test_changing_priority_recalculates_the_sla_deadlines(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-11 09:00', 'Asia/Dhaka'));
        $ticket = Ticket::factory()->priority(TicketPriority::Low)->create([
            'created_at' => now(),
            'sla_due_at' => now()->addDays(10),
            'sla_approaching_at' => now()->addDays(9),
        ]);
        $agent = User::factory()->agent()->create();

        $this->actingAs($agent)->patchJson("/api/tickets/{$ticket->id}", ['priority' => 'critical'])
            ->assertOk()
            ->assertJsonPath('priority', 'critical');

        $ticket->refresh();
        $this->assertSame('2026-10-11 11:00', $ticket->sla_due_at->setTimezone('Asia/Dhaka')->format('Y-m-d H:i'));
        $this->assertSame('2026-10-11 10:36', $ticket->sla_approaching_at->setTimezone('Asia/Dhaka')->format('Y-m-d H:i'));
        $this->assertDatabaseHas('ticket_activities', [
            'ticket_id' => $ticket->id,
            'type' => ActivityType::PriorityChanged->value,
            'actor_id' => $agent->id,
        ]);
    }

    public function test_raising_priority_on_a_breached_ticket_can_bring_it_back_within_sla(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-11 10:00', 'Asia/Dhaka'));
        $ticket = Ticket::factory()->critical()->breached()->create(['created_at' => now()->subHours(3)]);

        $this->actingAs(User::factory()->agent()->create())
            ->patchJson("/api/tickets/{$ticket->id}", ['priority' => 'low'])
            ->assertOk()
            ->assertJsonPath('sla.status', SlaStatus::Within->value)
            ->assertJsonPath('sla.escalated_at', null);
    }

    public function test_changing_category_is_recorded_with_names(): void
    {
        $ticket = Ticket::factory()->create();
        $category = Category::factory()->create(['name' => 'Billing']);

        $this->actingAs(User::factory()->administrator()->create())
            ->patchJson("/api/tickets/{$ticket->id}", ['category_id' => $category->id])
            ->assertOk()
            ->assertJsonPath('category.name', 'Billing');

        $activity = $ticket->activities()->where('type', ActivityType::CategoryChanged)->firstOrFail();
        $this->assertSame('Billing', $activity->payload['to']);
    }

    public function test_unchanged_values_do_not_create_activity_noise(): void
    {
        $ticket = Ticket::factory()->create(['subject' => 'Same']);

        $this->actingAs(User::factory()->agent()->create())
            ->patchJson("/api/tickets/{$ticket->id}", ['subject' => 'Same', 'priority' => $ticket->priority->value])
            ->assertOk();

        $this->assertDatabaseCount('ticket_activities', 0);
    }
}
