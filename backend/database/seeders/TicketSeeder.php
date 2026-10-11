<?php

namespace Database\Seeders;

use App\Enums\ActivityType;
use App\Enums\SlaStatus;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Category;
use App\Models\Ticket;
use App\Models\TicketActivity;
use App\Models\TicketReply;
use App\Models\User;
use App\Support\Sla\SlaCalculator;
use App\Support\Tickets\TicketNumberGenerator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TicketSeeder extends Seeder
{
    private const SUBJECTS = [
        'Hardware' => ['Printer on floor 3 stopped working', 'Laptop battery drains in an hour', 'Monitor flickers after wake-up'],
        'Software' => ['Cannot export report to PDF', 'Spreadsheet add-in crashes on open', 'Update stuck at 40%'],
        'Network' => ['VPN disconnects every few minutes', 'Wi-Fi unreachable in meeting room B', 'Slow file share access'],
        'Billing' => ['Invoice shows wrong amount', 'Charged twice for March', 'Need a copy of last year\'s invoices'],
        'Account Access' => ['Locked out after password reset', 'Two-factor code never arrives', 'Need access to the finance folder'],
        'General' => ['How do I change my notification settings?', 'Request for a second screen'],
    ];

    public function __construct(
        private readonly TicketNumberGenerator $ticketNumbers,
        private readonly SlaCalculator $sla,
    ) {}

    public function run(): void
    {
        if (Ticket::exists()) {
            return;
        }

        $customers = User::where('role', 'customer')->get();
        $agents = User::agents()->get();
        $categories = Category::all()->keyBy('name');

        foreach (self::SUBJECTS as $categoryName => $subjects) {
            foreach ($subjects as $subject) {
                $createdAt = Carbon::now()->subHours(fake()->numberBetween(1, 96));
                $priority = fake()->randomElement(TicketPriority::cases());
                $customer = $customers->random();
                $deadlines = $this->sla->deadlines($priority, $createdAt);

                $ticket = DB::transaction(fn () => Ticket::factory()->createdBy($customer)->create([
                    'ticket_number' => $this->ticketNumbers->next($createdAt),
                    'subject' => $subject,
                    'priority' => $priority,
                    'category_id' => $categories[$categoryName]->id,
                    'status' => TicketStatus::Open,
                    'sla_due_at' => $deadlines->dueAt,
                    'sla_approaching_at' => $deadlines->approachingAt,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]));

                $this->record($ticket, ActivityType::Created, $customer, at: $createdAt);
                $this->progress($ticket, $agents, $customer, $createdAt);
                $this->refreshSlaStatus($ticket);
            }
        }
    }

    private function refreshSlaStatus(Ticket $ticket): void
    {
        if ($ticket->status->isFinished()) {
            return;
        }

        $slaStatus = $ticket->liveSlaStatus();

        $ticket->update([
            'sla_status' => $slaStatus,
            'escalated_at' => $slaStatus === SlaStatus::Breached ? $ticket->sla_due_at : null,
        ]);
    }

    private function progress(Ticket $ticket, Collection $agents, User $customer, Carbon $createdAt): void
    {
        $finalStatus = fake()->randomElement([
            TicketStatus::Open,
            TicketStatus::Open,
            TicketStatus::InProgress,
            TicketStatus::InProgress,
            TicketStatus::InProgress,
            TicketStatus::Resolved,
            TicketStatus::Resolved,
            TicketStatus::Resolved,
            TicketStatus::Closed,
            TicketStatus::Closed,
        ]);

        if ($finalStatus === TicketStatus::Open) {
            return;
        }

        $agent = $agents->random();
        $assignedAt = $createdAt->copy()->addMinutes(fake()->numberBetween(5, 90));

        $ticket->update(['assigned_agent_id' => $agent->id, 'status' => TicketStatus::InProgress]);
        $this->record($ticket, ActivityType::Assigned, null, ['agent_id' => $agent->id, 'agent_name' => $agent->name], $assignedAt);
        $this->record($ticket, ActivityType::StatusChanged, $agent, ['from' => 'open', 'to' => 'in_progress'], $assignedAt);

        $repliedAt = $assignedAt->copy()->addMinutes(fake()->numberBetween(10, 120));
        $this->reply($ticket, $agent, 'Thanks for reporting this. I am looking into it now.', $repliedAt);
        $this->reply($ticket, $agent, 'Checked the asset history; this device had the same fault last quarter.', $repliedAt->copy()->addMinutes(2), internal: true);
        $this->reply($ticket, $customer, 'Thank you, let me know if you need anything from my side.', $repliedAt->copy()->addMinutes(25));

        if ($finalStatus === TicketStatus::InProgress) {
            return;
        }

        $resolvedAt = $repliedAt->copy()->addHours(fake()->numberBetween(1, 30));
        $withinSla = $resolvedAt->lessThanOrEqualTo($ticket->sla_due_at);

        $ticket->update([
            'status' => TicketStatus::Resolved,
            'resolved_at' => $resolvedAt,
            'resolved_within_sla' => $withinSla,
            'sla_status' => $withinSla ? SlaStatus::Within : SlaStatus::Breached,
            'escalated_at' => $withinSla ? null : $ticket->sla_due_at,
        ]);
        $this->record($ticket, ActivityType::StatusChanged, $agent, ['from' => 'in_progress', 'to' => 'resolved'], $resolvedAt);

        if ($finalStatus === TicketStatus::Closed) {
            $closedAt = $resolvedAt->copy()->addDay();
            $ticket->update(['status' => TicketStatus::Closed, 'closed_at' => $closedAt]);
            $this->record($ticket, ActivityType::StatusChanged, $agent, ['from' => 'resolved', 'to' => 'closed'], $closedAt);
        }
    }

    private function reply(Ticket $ticket, User $author, string $body, Carbon $at, bool $internal = false): void
    {
        TicketReply::factory()->for($ticket)->by($author)->create([
            'body' => $body,
            'is_internal' => $internal,
            'created_at' => $at,
            'updated_at' => $at,
        ]);

        $this->record($ticket, $internal ? ActivityType::NoteAdded : ActivityType::ReplyAdded, $author, at: $at);
    }

    private function record(Ticket $ticket, ActivityType $type, ?User $actor, ?array $payload = null, ?Carbon $at = null): void
    {
        TicketActivity::create([
            'ticket_id' => $ticket->id,
            'actor_id' => $actor?->id,
            'type' => $type,
            'payload' => $payload,
            'created_at' => $at ?? now(),
        ]);
    }
}
