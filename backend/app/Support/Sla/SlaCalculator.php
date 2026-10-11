<?php

namespace App\Support\Sla;

use App\Enums\TicketPriority;
use App\Support\BusinessHours\BusinessHoursCalculator;
use Carbon\CarbonInterface;

final readonly class SlaCalculator
{
    public function __construct(
        private SlaPolicies $policies,
        private BusinessHoursCalculator $businessHours,
    ) {}

    public function deadlines(TicketPriority $priority, CarbonInterface $from): SlaDeadlines
    {
        $rule = $this->policies->forPriority($priority);

        return new SlaDeadlines(
            approachingAt: $this->businessHours->addHours($from, $rule->approachingHours()),
            dueAt: $this->businessHours->addHours($from, $rule->resolutionHours),
        );
    }
}
