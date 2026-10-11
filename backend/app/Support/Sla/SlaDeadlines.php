<?php

namespace App\Support\Sla;

use App\Enums\SlaStatus;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

final readonly class SlaDeadlines
{
    public function __construct(
        public CarbonImmutable $approachingAt,
        public CarbonImmutable $dueAt,
    ) {}

    public function statusAt(CarbonInterface $moment): SlaStatus
    {
        return match (true) {
            $moment->greaterThanOrEqualTo($this->dueAt) => SlaStatus::Breached,
            $moment->greaterThanOrEqualTo($this->approachingAt) => SlaStatus::Approaching,
            default => SlaStatus::Within,
        };
    }
}
