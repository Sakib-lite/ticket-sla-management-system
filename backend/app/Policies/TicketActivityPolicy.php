<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketActivityPolicy
{
    public function viewAny(User $user, Ticket $ticket): bool
    {
        return $user->can('view', $ticket);
    }
}
