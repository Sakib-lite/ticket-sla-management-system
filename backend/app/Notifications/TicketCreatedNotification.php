<?php

namespace App\Notifications;

use App\Models\User;

class TicketCreatedNotification extends TicketNotification
{
    public function kind(): string
    {
        return 'ticket_created';
    }

    protected function subject(User $notifiable): string
    {
        return 'We received your ticket';
    }

    protected function message(User $notifiable): string
    {
        return "Your ticket \"{$this->ticket->subject}\" has been logged. We will keep you posted here and by email.";
    }
}
