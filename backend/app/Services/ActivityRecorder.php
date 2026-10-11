<?php

namespace App\Services;

use App\Enums\ActivityType;
use App\Models\Ticket;
use App\Models\TicketActivity;
use App\Models\User;

final class ActivityRecorder
{
    /** @param  array<string, mixed>  $payload */
    public function record(Ticket $ticket, ActivityType $type, ?User $actor, array $payload = []): TicketActivity
    {
        return $ticket->activities()->create([
            'actor_id' => $actor?->id,
            'type' => $type,
            'payload' => $payload ?: null,
            'created_at' => now(),
        ]);
    }
}
