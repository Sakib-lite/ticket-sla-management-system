<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketReplyPolicy
{
    public function viewAny(User $user, Ticket $ticket): bool
    {
        return $user->can('view', $ticket);
    }

    public function create(User $user, Ticket $ticket): bool
    {
        return $user->can('view', $ticket);
    }

    public function createInternal(User $user, Ticket $ticket): bool
    {
        return $user->isStaff();
    }
}
