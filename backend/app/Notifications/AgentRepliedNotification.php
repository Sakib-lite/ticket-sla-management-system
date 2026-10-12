<?php

namespace App\Notifications;

use App\Models\TicketReply;
use App\Models\User;
use Illuminate\Support\Str;

class AgentRepliedNotification extends TicketNotification
{
    public function __construct(public readonly TicketReply $reply)
    {
        parent::__construct($reply->ticket);
    }

    public function kind(): string
    {
        return 'agent_replied';
    }

    protected function subject(User $notifiable): string
    {
        return 'New reply on your ticket';
    }

    protected function message(User $notifiable): string
    {
        return "{$this->reply->author->name} replied: \"".Str::limit($this->reply->body, 120).'"';
    }
}
