<?php

namespace App\Services\Assignment;

use App\Models\Ticket;
use App\Models\User;

interface AgentAssigner
{
    public function pick(Ticket $ticket): ?User;
}
