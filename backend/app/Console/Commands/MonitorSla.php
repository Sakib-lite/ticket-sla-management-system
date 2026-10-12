<?php

namespace App\Console\Commands;

use App\Services\Sla\SlaMonitor;
use Illuminate\Console\Command;

class MonitorSla extends Command
{
    protected $signature = 'sla:monitor';

    protected $description = 'Flag tickets approaching their SLA deadline and escalate the ones that breached it';

    public function handle(SlaMonitor $monitor): int
    {
        $report = $monitor->run();

        $this->info("Approaching: {$report->approaching}, escalated: {$report->breached}.");

        return self::SUCCESS;
    }
}
