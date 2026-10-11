<?php

namespace App\Services\Tickets;

use App\Enums\TicketStatus;
use DomainException;

class InvalidStatusTransitionException extends DomainException
{
    public function __construct(TicketStatus $from, TicketStatus $to)
    {
        parent::__construct("A ticket cannot move from {$from->label()} to {$to->label()}.");
    }
}
