<?php

namespace App\Notifications;

use App\Models\User;

class SlaApproachingNotification extends TicketNotification
{
    public function kind(): string
    {
        return 'sla_approaching';
    }

    protected function subject(User $notifiable): string
    {
        return 'SLA deadline approaching';
    }

    protected function message(User $notifiable): string
    {
        return "\"{$this->ticket->subject}\" is due ".$this->ticket->sla_due_at->diffForHumans().' and is still unresolved.';
    }
}
