<?php

namespace Tests\Feature\Tickets;

use App\Enums\ActivityType;
use App\Models\Category;
use App\Models\Ticket;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\SlaPolicySeeder;
use Tests\TestCase;

class CreateTicketTest extends TestCase
{
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SlaPolicySeeder::class);
        $this->category = Category::factory()->create();
    }

    public function test_a_customer_creates_a_ticket_with_a_number_and_sla_deadlines(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-08 17:00', 'Asia/Dhaka'));
        $customer = User::factory()->create();

        $response = $this->actingAs($customer)->postJson('/api/tickets', [
            'subject' => 'Printer on floor 3 stopped working',
            'description' => 'It shows a paper jam but there is no paper inside.',
            'priority' => 'critical',
            'category_id' => $this->category->id,
        ]);

        $response->assertCreated()
            ->assertJsonPath('ticket_number', 'TKT-2026-000001')
            ->assertJsonPath('status', 'open')
            ->assertJsonPath('priority', 'critical')
            ->assertJsonPath('creator.id', $customer->id)
            ->assertJsonPath('category.id', $this->category->id)
            ->assertJsonPath('assignee', null)
            ->assertJsonPath('sla.status', 'within');

        $ticket = Ticket::firstOrFail();
        $this->assertSame('2026-10-11 10:00', $ticket->sla_due_at->setTimezone('Asia/Dhaka')->format('Y-m-d H:i'));
        $this->assertSame(ActivityType::Created, $ticket->activities()->firstOrFail()->type);
        $this->assertSame($customer->id, $ticket->activities()->firstOrFail()->actor_id);
    }

    public function test_an_agent_can_raise_a_ticket_on_behalf_of_a_customer(): void
    {
        $agent = User::factory()->agent()->create();
        $customer = User::factory()->create();

        $response = $this->actingAs($agent)->postJson('/api/tickets', [
            'subject' => 'Phoned in: VPN keeps dropping',
            'description' => 'Customer reports disconnects every ten minutes.',
            'priority' => 'high',
            'category_id' => $this->category->id,
            'customer_id' => $customer->id,
        ]);

        $response->assertCreated()->assertJsonPath('creator.id', $customer->id);
        $this->assertSame($agent->id, Ticket::firstOrFail()->activities()->firstOrFail()->actor_id);
    }

    public function test_a_customer_cannot_create_a_ticket_for_someone_else(): void
    {
        $customer = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($customer)->postJson('/api/tickets', [
            'subject' => 'Subject',
            'description' => 'Description',
            'priority' => 'low',
            'category_id' => $this->category->id,
            'customer_id' => $other->id,
        ])->assertUnprocessable()->assertJsonValidationErrors('customer_id');
    }

    public function test_invalid_input_is_rejected(): void
    {
        $inactive = Category::factory()->inactive()->create();

        $this->actingAs(User::factory()->create())->postJson('/api/tickets', [
            'subject' => '',
            'description' => '',
            'priority' => 'urgent',
            'category_id' => $inactive->id,
        ])->assertUnprocessable()->assertJsonValidationErrors(['subject', 'description', 'priority', 'category_id']);
    }

    public function test_sequential_tickets_get_sequential_numbers(): void
    {
        $customer = User::factory()->create();
        $payload = ['subject' => 'S', 'description' => 'D', 'priority' => 'low', 'category_id' => $this->category->id];

        $this->actingAs($customer)->postJson('/api/tickets', $payload)->assertJsonPath('ticket_number', 'TKT-'.now()->year.'-000001');
        $this->actingAs($customer)->postJson('/api/tickets', $payload)->assertJsonPath('ticket_number', 'TKT-'.now()->year.'-000002');
    }

    public function test_guests_are_rejected(): void
    {
        $this->postJson('/api/tickets', [])->assertUnauthorized();
    }
}
