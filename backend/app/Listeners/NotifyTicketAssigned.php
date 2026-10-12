<?php

namespace App\Listeners;

use App\Events\TicketAssigned;
use App\Models\User;
use App\Notifications\TicketAssignedNotification;
use App\Support\Notifications\NotificationRecipients;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Notification;

final class NotifyTicketAssigned implements ShouldQueueAfterCommit
{
    public function __construct(private readonly NotificationRecipients $recipients) {}

    public function handle(TicketAssigned $event): void
    {
        $audience = new Collection([$event->agent, $event->ticket->creator]);
        $audience = $audience->unique('id')->filter(fn (User $user) => $user->isStaff() || $user->is($event->ticket->creator));

        Notification::send(
            $this->recipients->without($audience, $event->actor),
            new TicketAssignedNotification($event->ticket, $event->agent),
        );
    }
}
