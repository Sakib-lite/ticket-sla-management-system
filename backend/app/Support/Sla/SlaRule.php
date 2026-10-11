<?php

namespace App\Support\Sla;

use App\Enums\TicketPriority;
use App\Models\SlaPolicy;

final readonly class SlaRule
{
    public function __construct(
        public TicketPriority $priority,
        public int $resolutionHours,
        public int $approachingThresholdPercent,
    ) {}

    public static function fromPolicy(SlaPolicy $policy): self
    {
        return new self(
            priority: $policy->priority,
            resolutionHours: $policy->resolution_hours,
            approachingThresholdPercent: $policy->approaching_threshold_percent,
        );
    }

    /** @param array{priority: string, resolution_hours: int, approaching_threshold_percent: int} $data */
    public static function fromArray(array $data): self
    {
        return new self(
            priority: TicketPriority::from($data['priority']),
            resolutionHours: $data['resolution_hours'],
            approachingThresholdPercent: $data['approaching_threshold_percent'],
        );
    }

    /** @return array{priority: string, resolution_hours: int, approaching_threshold_percent: int} */
    public function toArray(): array
    {
        return [
            'priority' => $this->priority->value,
            'resolution_hours' => $this->resolutionHours,
            'approaching_threshold_percent' => $this->approachingThresholdPercent,
        ];
    }

    public function approachingHours(): float
    {
        return $this->resolutionHours * $this->approachingThresholdPercent / 100;
    }
}
