<?php

namespace App\Support\BusinessHours;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

final readonly class BusinessHoursCalculator
{
    public function __construct(private BusinessSchedule $schedule) {}

    public function addHours(CarbonInterface $from, float $hours): CarbonImmutable
    {
        $remainingMinutes = (int) round($hours * 60);
        $cursor = $this->nextWorkingMoment($from);

        while (true) {
            $minutesUntilClose = (int) $cursor->diffInMinutes($this->schedule->closingOn($cursor));

            if ($remainingMinutes <= $minutesUntilClose) {
                return $cursor->addMinutes($remainingMinutes)->utc();
            }

            $remainingMinutes -= $minutesUntilClose;
            $cursor = $this->schedule->nextOpeningAfter($cursor);
        }
    }

    public function nextWorkingMoment(CarbonInterface $moment): CarbonImmutable
    {
        $local = $this->schedule->localize($moment);

        if ($this->schedule->isWorkingDay($local)) {
            if ($local->lessThan($this->schedule->openingOn($local))) {
                return $this->schedule->openingOn($local);
            }

            if ($local->lessThan($this->schedule->closingOn($local))) {
                return $local;
            }
        }

        return $this->schedule->nextOpeningAfter($local);
    }
}
