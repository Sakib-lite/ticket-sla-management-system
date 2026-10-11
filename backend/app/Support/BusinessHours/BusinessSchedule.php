<?php

namespace App\Support\BusinessHours;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use InvalidArgumentException;

final readonly class BusinessSchedule
{
    /** @param list<int> $workingDays */
    public function __construct(
        public string $timezone,
        public array $workingDays,
        public string $opensAt,
        public string $closesAt,
    ) {
        if ($workingDays === []) {
            throw new InvalidArgumentException('A business schedule needs at least one working day.');
        }

        if ($opensAt >= $closesAt) {
            throw new InvalidArgumentException('Business hours must open before they close.');
        }
    }

    /** @param array{timezone: string, days: list<int>, opens_at: string, closes_at: string} $config */
    public static function fromConfig(array $config): self
    {
        return new self(
            timezone: $config['timezone'],
            workingDays: array_map(intval(...), $config['days']),
            opensAt: $config['opens_at'],
            closesAt: $config['closes_at'],
        );
    }

    public function localize(CarbonInterface $moment): CarbonImmutable
    {
        return CarbonImmutable::instance($moment)->setTimezone($this->timezone);
    }

    public function isWorkingDay(CarbonInterface $localDate): bool
    {
        return in_array($localDate->dayOfWeek, $this->workingDays, true);
    }

    public function openingOn(CarbonInterface $localDate): CarbonImmutable
    {
        return $this->at($localDate, $this->opensAt);
    }

    public function closingOn(CarbonInterface $localDate): CarbonImmutable
    {
        return $this->at($localDate, $this->closesAt);
    }

    public function nextOpeningAfter(CarbonInterface $moment): CarbonImmutable
    {
        $day = $this->localize($moment)->addDay();

        while (! $this->isWorkingDay($day)) {
            $day = $day->addDay();
        }

        return $this->openingOn($day);
    }

    private function at(CarbonInterface $localDate, string $time): CarbonImmutable
    {
        [$hour, $minute] = array_map(intval(...), explode(':', $time));

        return $this->localize($localDate)->setTime($hour, $minute);
    }
}
