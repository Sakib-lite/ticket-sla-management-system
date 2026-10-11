<?php

namespace App\Services\Assignment;

use App\Enums\ActivityType;
use App\Models\Ticket;
use App\Models\User;
use App\Services\ActivityRecorder;
use Illuminate\Support\Facades\DB;

final readonly class AssignmentService
{
    public function __construct(
        private AgentAssigner $assigner,
        private ActivityRecorder $activities,
        private bool $autoAssignEnabled,
    ) {}

    public function assign(Ticket $ticket, User $agent, User $actor): Ticket
    {
        return DB::transaction(function () use ($ticket, $agent, $actor) {
            $previous = $ticket->assignee;

            if ($previous?->is($agent)) {
                return $ticket;
            }

            $this->transferTo($ticket, $agent);

            $this->activities->record($ticket, ActivityType::Assigned, $actor, [
                'from' => $previous?->name,
                'to' => $agent->name,
                'agent_id' => $agent->id,
            ]);

            return $ticket;
        });
    }

    public function claim(Ticket $ticket, User $agent): Ticket
    {
        return DB::transaction(function () use ($ticket, $agent) {
            $claimed = Ticket::query()
                ->whereKey($ticket->id)
                ->whereNull('assigned_agent_id')
                ->update(['assigned_agent_id' => $agent->id, 'updated_at' => now()]);

            if ($claimed === 0) {
                throw new TicketAlreadyAssignedException($ticket->fresh(['assignee']));
            }

            $agent->forceFill(['last_assigned_at' => now()])->save();

            $this->activities->record($ticket, ActivityType::Claimed, $agent, [
                'to' => $agent->name,
                'agent_id' => $agent->id,
            ]);

            return $ticket->refresh();
        });
    }

    public function unassign(Ticket $ticket, User $actor): Ticket
    {
        return DB::transaction(function () use ($ticket, $actor) {
            $previous = $ticket->assignee;

            if ($previous === null) {
                return $ticket;
            }

            $ticket->assignee()->dissociate();
            $ticket->save();

            $this->activities->record($ticket, ActivityType::Unassigned, $actor, [
                'from' => $previous->name,
            ]);

            return $ticket;
        });
    }

    public function autoAssign(Ticket $ticket): ?User
    {
        if (! $this->autoAssignEnabled) {
            return null;
        }

        $agent = $this->assigner->pick($ticket);

        if ($agent === null) {
            return null;
        }

        $this->transferTo($ticket, $agent);

        $this->activities->record($ticket, ActivityType::AutoAssigned, null, [
            'to' => $agent->name,
            'agent_id' => $agent->id,
        ]);

        return $agent;
    }

    private function transferTo(Ticket $ticket, User $agent): void
    {
        $ticket->assignee()->associate($agent);
        $ticket->save();

        $agent->forceFill(['last_assigned_at' => now()])->save();
    }
}
