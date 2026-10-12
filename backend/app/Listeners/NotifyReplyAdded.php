<?php

namespace App\Listeners;

use App\Events\ReplyAdded;
use App\Notifications\AgentRepliedNotification;
use App\Notifications\CustomerRepliedNotification;
use App\Support\Notifications\NotificationRecipients;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Support\Facades\Notification;

final class NotifyReplyAdded implements ShouldQueueAfterCommit
{
    public function __construct(private readonly NotificationRecipients $recipients) {}

    public function handle(ReplyAdded $event): void
    {
        $ticket = $event->reply->ticket;

        if ($event->author->isStaff()) {
            Notification::send(
                $this->recipients->without(collect([$ticket->creator]), $event->author),
                new AgentRepliedNotification($event->reply),
            );

            return;
        }

        Notification::send(
            $this->recipients->without($this->recipients->staffFor($ticket), $event->author),
            new CustomerRepliedNotification($event->reply),
        );
    }
}
