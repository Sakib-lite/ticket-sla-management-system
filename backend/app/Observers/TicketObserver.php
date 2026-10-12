<?php

namespace App\Observers;

use App\Models\Ticket;
use App\Support\Dashboard\DashboardCache;

final readonly class TicketObserver
{
    public function __construct(private DashboardCache $cache) {}

    public function saved(Ticket $ticket): void
    {
        $this->cache->bump();
    }

    public function deleted(Ticket $ticket): void
    {
        $this->cache->bump();
    }
}
