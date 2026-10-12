<?php

namespace App\Notifications;

use App\Models\Ticket;
use App\Models\User;

class TicketAssignedNotification extends TicketNotification
{
    public function __construct(Ticket $ticket, public readonly User $agent)
    {
        parent::__construct($ticket);
    }

    public function kind(): string
    {
        return 'ticket_assigned';
    }

    protected function subject(User $notifiable): string
    {
        return $notifiable->is($this->agent) ? 'A ticket was assigned to you' : 'Your ticket has an agent';
    }

    protected function message(User $notifiable): string
    {
        if ($notifiable->is($this->agent)) {
            return "\"{$this->ticket->subject}\" is now assigned to you.";
        }

        return "{$this->agent->name} is now handling your ticket \"{$this->ticket->subject}\".";
    }
}
