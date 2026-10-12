<?php

namespace App\Events;

use App\Models\TicketReply;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class ReplyAdded
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly TicketReply $reply,
        public readonly User $author,
    ) {}
}
