<?php

namespace App\Listeners;

use App\Events\SlaApproaching;
use App\Notifications\SlaApproachingNotification;
use App\Support\Notifications\NotificationRecipients;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Support\Facades\Notification;

final class NotifySlaApproaching implements ShouldQueueAfterCommit
{
    public function __construct(private readonly NotificationRecipients $recipients) {}

    public function handle(SlaApproaching $event): void
    {
        Notification::send(
            $this->recipients->escalationFor($event->ticket),
            new SlaApproachingNotification($event->ticket),
        );
    }
}
