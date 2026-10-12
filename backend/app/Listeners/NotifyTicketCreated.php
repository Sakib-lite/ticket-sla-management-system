<?php

namespace App\Listeners;

use App\Events\TicketCreated;
use App\Notifications\TicketCreatedNotification;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;

final class NotifyTicketCreated implements ShouldQueueAfterCommit
{
    public function handle(TicketCreated $event): void
    {
        $event->ticket->creator->notify(new TicketCreatedNotification($event->ticket));
    }
}
