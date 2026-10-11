<?php

namespace App\Support\Activities;

use App\Enums\ActivityType;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\TicketActivity;

final class ActivityPresenter
{
    public function describe(TicketActivity $activity): string
    {
        $actor = $activity->actor?->name ?? 'System';
        $payload = $activity->payload ?? [];

        return match ($activity->type) {
            ActivityType::Created => "{$actor} created the ticket",
            ActivityType::Imported => "{$actor} imported the ticket",
            ActivityType::Assigned => $this->assigned($actor, $payload),
            ActivityType::AutoAssigned => "Automatically assigned to {$payload['to']}",
            ActivityType::Claimed => "{$actor} claimed the ticket",
            ActivityType::Unassigned => "{$actor} unassigned {$payload['from']}",
            ActivityType::StatusChanged => sprintf(
                '%s changed the status from %s to %s',
                $actor,
                TicketStatus::from($payload['from'])->label(),
                TicketStatus::from($payload['to'])->label(),
            ),
            ActivityType::PriorityChanged => sprintf(
                '%s changed the priority from %s to %s',
                $actor,
                TicketPriority::from($payload['from'])->label(),
                TicketPriority::from($payload['to'])->label(),
            ),
            ActivityType::CategoryChanged => "{$actor} moved the ticket from {$payload['from']} to {$payload['to']}",
            ActivityType::SubjectChanged => "{$actor} changed the subject to \"{$payload['to']}\"",
            ActivityType::ReplyAdded => "{$actor} replied",
            ActivityType::NoteAdded => "{$actor} added an internal note",
            ActivityType::AttachmentAdded => $this->attached($actor, $payload),
            ActivityType::SlaApproaching => 'SLA deadline is approaching',
            ActivityType::Escalated => 'Ticket escalated: SLA breached',
        };
    }

    /** @param  array<string, mixed>  $payload */
    private function assigned(string $actor, array $payload): string
    {
        if (! empty($payload['from'])) {
            return "{$actor} reassigned the ticket from {$payload['from']} to {$payload['to']}";
        }

        return "{$actor} assigned the ticket to {$payload['to']}";
    }

    /** @param  array<string, mixed>  $payload */
    private function attached(string $actor, array $payload): string
    {
        $count = $payload['count'] ?? count($payload['names'] ?? []);
        $noun = $count === 1 ? 'file' : 'files';
        $names = implode(', ', $payload['names'] ?? []);

        return "{$actor} attached {$count} {$noun}: {$names}";
    }
}
