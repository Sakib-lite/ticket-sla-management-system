<?php

namespace App\Events;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class TicketCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Ticket $ticket,
        public readonly User $actor,
    ) {}
}
