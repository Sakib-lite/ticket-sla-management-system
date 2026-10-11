<?php

namespace App\Services\Tickets;

use App\Enums\TicketStatus;
use App\Exceptions\UnprocessableException;

class InvalidStatusTransitionException extends UnprocessableException
{
    public function __construct(TicketStatus $from, TicketStatus $to)
    {
        parent::__construct("A ticket cannot move from {$from->label()} to {$to->label()}.", 'status');
    }
}
