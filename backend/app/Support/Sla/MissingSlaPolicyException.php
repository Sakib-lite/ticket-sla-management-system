<?php

namespace App\Support\Sla;

use App\Enums\TicketPriority;
use RuntimeException;

class MissingSlaPolicyException extends RuntimeException
{
    public function __construct(TicketPriority $priority)
    {
        parent::__construct("No SLA policy is configured for the {$priority->label()} priority.");
    }
}
