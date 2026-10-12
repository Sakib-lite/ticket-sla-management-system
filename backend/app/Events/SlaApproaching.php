<?php

namespace App\Events;

use App\Models\Ticket;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class SlaApproaching
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Ticket $ticket) {}
}
