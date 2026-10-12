<?php

namespace App\Notifications;

use App\Models\TicketReply;
use App\Models\User;
use Illuminate\Support\Str;

class CustomerRepliedNotification extends TicketNotification
{
    public function __construct(public readonly TicketReply $reply)
    {
        parent::__construct($reply->ticket);
    }

    public function kind(): string
    {
        return 'customer_replied';
    }

    protected function subject(User $notifiable): string
    {
        return 'The customer replied';
    }

    protected function message(User $notifiable): string
    {
        return "{$this->reply->author->name} replied: \"".Str::limit($this->reply->body, 120).'"';
    }
}
