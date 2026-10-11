<?php

namespace App\Enums;

enum TicketStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case WaitingForCustomer = 'waiting_for_customer';
    case Resolved = 'resolved';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::InProgress => 'In Progress',
            self::WaitingForCustomer => 'Waiting for Customer',
            self::Resolved => 'Resolved',
            self::Closed => 'Closed',
        };
    }

    /** @return list<self> */
    public function transitions(): array
    {
        return match ($this) {
            self::Open => [self::InProgress, self::WaitingForCustomer, self::Resolved, self::Closed],
            self::InProgress => [self::Open, self::WaitingForCustomer, self::Resolved, self::Closed],
            self::WaitingForCustomer => [self::Open, self::InProgress, self::Resolved, self::Closed],
            self::Resolved => [self::Open, self::Closed],
            self::Closed => [],
        };
    }

    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->transitions(), true);
    }

    public function isActive(): bool
    {
        return ! $this->isFinished();
    }

    public function isFinished(): bool
    {
        return in_array($this, [self::Resolved, self::Closed], true);
    }

    /** @return list<self> */
    public static function active(): array
    {
        return array_values(array_filter(self::cases(), fn (self $status) => $status->isActive()));
    }

    /** @return list<self> */
    public static function finished(): array
    {
        return array_values(array_filter(self::cases(), fn (self $status) => $status->isFinished()));
    }
}
