<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Ticket $ticket): bool
    {
        return $user->isStaff() || $ticket->isOwnedBy($user);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Ticket $ticket): bool
    {
        if ($user->isStaff()) {
            return true;
        }

        return $ticket->isOwnedBy($user) && $ticket->status->isActive();
    }

    public function changePriority(User $user, Ticket $ticket): bool
    {
        return $user->isStaff();
    }

    public function changeStatus(User $user, Ticket $ticket): bool
    {
        return $user->isStaff();
    }

    public function attach(User $user, Ticket $ticket): bool
    {
        return $this->view($user, $ticket) && $ticket->status->isActive();
    }

    public function assign(User $user, Ticket $ticket): bool
    {
        return $user->isStaff();
    }

    public function claim(User $user, Ticket $ticket): bool
    {
        return $user->isAgent() && $ticket->status->isActive();
    }
}
