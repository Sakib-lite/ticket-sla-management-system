<?php

namespace App\Services\Tickets;

use App\Enums\ActivityType;
use App\Enums\SlaStatus;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Category;
use App\Models\Ticket;
use App\Models\User;
use App\Services\ActivityRecorder;
use App\Services\Assignment\AssignmentService;
use App\Support\Sla\SlaCalculator;
use App\Support\Tickets\TicketNumberGenerator;
use Illuminate\Support\Facades\DB;

final readonly class TicketService
{
    public function __construct(
        private TicketNumberGenerator $ticketNumbers,
        private SlaCalculator $sla,
        private ActivityRecorder $activities,
        private AssignmentService $assignments,
    ) {}

    /** @param  array{subject: string, description: string, priority: string, category_id: int}  $attributes */
    public function create(User $creator, User $customer, array $attributes): Ticket
    {
        return DB::transaction(function () use ($creator, $customer, $attributes) {
            $priority = TicketPriority::from($attributes['priority']);
            $deadlines = $this->sla->deadlines($priority, now());

            $ticket = Ticket::create([
                'ticket_number' => $this->ticketNumbers->next(),
                'subject' => $attributes['subject'],
                'description' => $attributes['description'],
                'priority' => $priority,
                'category_id' => $attributes['category_id'],
                'status' => TicketStatus::Open,
                'created_by' => $customer->id,
                'sla_due_at' => $deadlines->dueAt,
                'sla_approaching_at' => $deadlines->approachingAt,
                'sla_status' => SlaStatus::Within,
            ]);

            $this->activities->record($ticket, ActivityType::Created, $creator);
            $this->assignments->autoAssign($ticket);

            return $ticket;
        }, attempts: 3);
    }

    /** @param  array{subject?: string, description?: string, priority?: string, category_id?: int}  $attributes */
    public function update(Ticket $ticket, User $actor, array $attributes): Ticket
    {
        return DB::transaction(function () use ($ticket, $actor, $attributes) {
            if (array_key_exists('subject', $attributes)) {
                $this->changeSubject($ticket, $actor, $attributes['subject']);
            }

            if (array_key_exists('description', $attributes)) {
                $ticket->description = $attributes['description'];
            }

            if (array_key_exists('category_id', $attributes)) {
                $this->changeCategory($ticket, $actor, Category::findOrFail($attributes['category_id']));
            }

            if (array_key_exists('priority', $attributes)) {
                $this->changePriority($ticket, $actor, TicketPriority::from($attributes['priority']));
            }

            $ticket->save();

            return $ticket;
        });
    }

    public function changeStatus(Ticket $ticket, User $actor, TicketStatus $status): Ticket
    {
        if (! $ticket->status->canTransitionTo($status)) {
            throw new InvalidStatusTransitionException($ticket->status, $status);
        }

        return DB::transaction(function () use ($ticket, $actor, $status) {
            $previous = $ticket->status;

            $ticket->status = $status;
            $this->applyStatusTimestamps($ticket, $status);
            $ticket->save();

            $this->activities->record($ticket, ActivityType::StatusChanged, $actor, [
                'from' => $previous->value,
                'to' => $status->value,
            ]);

            return $ticket;
        });
    }

    private function changeSubject(Ticket $ticket, User $actor, string $subject): void
    {
        if ($ticket->subject === $subject) {
            return;
        }

        $this->activities->record($ticket, ActivityType::SubjectChanged, $actor, [
            'from' => $ticket->subject,
            'to' => $subject,
        ]);

        $ticket->subject = $subject;
    }

    private function changeCategory(Ticket $ticket, User $actor, Category $category): void
    {
        if ($ticket->category_id === $category->id) {
            return;
        }

        $this->activities->record($ticket, ActivityType::CategoryChanged, $actor, [
            'from' => $ticket->category->name,
            'to' => $category->name,
        ]);

        $ticket->category()->associate($category);
    }

    private function changePriority(Ticket $ticket, User $actor, TicketPriority $priority): void
    {
        if ($ticket->priority === $priority) {
            return;
        }

        $this->activities->record($ticket, ActivityType::PriorityChanged, $actor, [
            'from' => $ticket->priority->value,
            'to' => $priority->value,
        ]);

        $deadlines = $this->sla->deadlines($priority, $ticket->created_at);

        $ticket->priority = $priority;
        $ticket->sla_due_at = $deadlines->dueAt;
        $ticket->sla_approaching_at = $deadlines->approachingAt;
        $ticket->sla_status = $ticket->liveSlaStatus();

        if ($ticket->sla_status !== SlaStatus::Breached) {
            $ticket->escalated_at = null;
        }
    }

    private function applyStatusTimestamps(Ticket $ticket, TicketStatus $status): void
    {
        match ($status) {
            TicketStatus::Resolved => $this->markResolved($ticket),
            TicketStatus::Closed => $this->markClosed($ticket),
            default => $this->markReopened($ticket),
        };
    }

    private function markResolved(Ticket $ticket): void
    {
        $ticket->sla_status = $ticket->liveSlaStatus();
        $ticket->resolved_at = now();
        $ticket->resolved_within_sla = $ticket->sla_status !== SlaStatus::Breached;
    }

    private function markClosed(Ticket $ticket): void
    {
        if ($ticket->resolved_at === null) {
            $this->markResolved($ticket);
        }

        $ticket->closed_at = now();
    }

    private function markReopened(Ticket $ticket): void
    {
        if ($ticket->resolved_at === null) {
            return;
        }

        $ticket->resolved_at = null;
        $ticket->closed_at = null;
        $ticket->resolved_within_sla = null;
        $ticket->sla_status = $ticket->slaDeadlines()->statusAt(now());
    }
}
