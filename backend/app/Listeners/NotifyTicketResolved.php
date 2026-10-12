<?php

namespace App\Listeners;

use App\Events\TicketResolved;
use App\Notifications\TicketResolvedNotification;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;

final class NotifyTicketResolved implements ShouldQueueAfterCommit
{
    public function handle(TicketResolved $event): void
    {
        if ($event->actor->is($event->ticket->creator)) {
            return;
        }

        $event->ticket->creator->notify(new TicketResolvedNotification($event->ticket, $event->actor));
    }
}
