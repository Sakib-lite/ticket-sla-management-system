<?php

namespace App\Enums;

enum SlaStatus: string
{
    case Within = 'within';
    case Approaching = 'approaching';
    case Breached = 'breached';

    public function label(): string
    {
        return match ($this) {
            self::Within => 'Within SLA',
            self::Approaching => 'Approaching SLA breach',
            self::Breached => 'SLA breached',
        };
    }

    public function isAfter(self $other): bool
    {
        return $this->order() > $other->order();
    }

    private function order(): int
    {
        return match ($this) {
            self::Within => 0,
            self::Approaching => 1,
            self::Breached => 2,
        };
    }
}
