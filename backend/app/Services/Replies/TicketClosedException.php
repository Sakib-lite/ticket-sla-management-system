<?php

namespace App\Services\Replies;

use App\Exceptions\UnprocessableException;
use App\Models\Ticket;

class TicketClosedException extends UnprocessableException
{
    public function __construct(Ticket $ticket)
    {
        parent::__construct("{$ticket->ticket_number} is closed and no longer accepts replies.", 'body');
    }
}
