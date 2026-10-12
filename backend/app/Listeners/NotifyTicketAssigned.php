<?php

namespace App\Listeners;

use App\Events\TicketAssigned;
use App\Notifications\TicketAssignedNotification;
use App\Support\Notifications\NotificationRecipients;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Support\Facades\Notification;

final class NotifyTicketAssigned implements ShouldQueueAfterCommit
{
    public function __construct(private readonly NotificationRecipients $recipients) {}

    public function handle(TicketAssigned $event): void
    {
        $audience = collect([$event->agent, $event->ticket->creator])->unique('id');

        Notification::send(
            $this->recipients->without($audience, $event->actor),
            new TicketAssignedNotification($event->ticket, $event->agent),
        );
    }
}
