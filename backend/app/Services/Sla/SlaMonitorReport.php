<?php

namespace App\Services\Sla;

final readonly class SlaMonitorReport
{
    public function __construct(
        public int $approaching,
        public int $breached,
    ) {}
}
