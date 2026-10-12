<?php

namespace App\Services\Assignment;

use App\Models\Ticket;
use App\Models\User;

final class LeastWorkloadAssigner implements AgentAssigner
{
    public function pick(Ticket $ticket): ?User
    {
        return User::query()
            ->agents()
            ->available()
            ->withCount('activeAssignedTickets as active_tickets_count')
            ->orderBy('active_tickets_count')
            ->orderByRaw('last_assigned_at IS NULL DESC')
            ->orderBy('last_assigned_at')
            ->orderBy('id')
            ->first();
    }
}
