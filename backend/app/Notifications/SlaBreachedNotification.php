<?php

namespace App\Notifications;

use App\Models\User;

class SlaBreachedNotification extends TicketNotification
{
    public function kind(): string
    {
        return 'sla_breached';
    }

    protected function subject(User $notifiable): string
    {
        return 'SLA breached';
    }

    protected function message(User $notifiable): string
    {
        return "\"{$this->ticket->subject}\" missed its SLA deadline and has been escalated.";
    }
}
