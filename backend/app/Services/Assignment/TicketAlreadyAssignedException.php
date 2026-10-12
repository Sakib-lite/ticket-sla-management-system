<?php

namespace App\Services\Assignment;

use App\Models\Ticket;
use RuntimeException;

class TicketAlreadyAssignedException extends RuntimeException
{
    public function __construct(public readonly Ticket $ticket)
    {
        $assignee = $ticket->assignee->name ?? 'another agent';

        parent::__construct("{$ticket->ticket_number} was just taken by {$assignee}.");
    }
}
