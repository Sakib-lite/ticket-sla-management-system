<?php

namespace App\Listeners;

use App\Events\SlaBreached;
use App\Notifications\SlaBreachedNotification;
use App\Support\Notifications\NotificationRecipients;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Support\Facades\Notification;

final class NotifySlaBreached implements ShouldQueueAfterCommit
{
    public function __construct(private readonly NotificationRecipients $recipients) {}

    public function handle(SlaBreached $event): void
    {
        Notification::send(
            $this->recipients->escalationFor($event->ticket),
            new SlaBreachedNotification($event->ticket),
        );
    }
}
