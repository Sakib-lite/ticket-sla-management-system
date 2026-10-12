<?php

namespace App\Notifications;

use App\Models\Ticket;
use App\Models\User;

class TicketResolvedNotification extends TicketNotification
{
    public function __construct(Ticket $ticket, public readonly User $resolver)
    {
        parent::__construct($ticket);
    }

    public function kind(): string
    {
        return 'ticket_resolved';
    }

    protected function subject(User $notifiable): string
    {
        return 'Your ticket has been resolved';
    }

    protected function message(User $notifiable): string
    {
        return "{$this->resolver->name} marked \"{$this->ticket->subject}\" as resolved. Reply on the ticket if the problem is not fixed.";
    }
}
