<?php

namespace App\Enums;

enum Role: string
{
    case Administrator = 'administrator';
    case Agent = 'agent';
    case Customer = 'customer';

    public function label(): string
    {
        return match ($this) {
            self::Administrator => 'Administrator',
            self::Agent => 'Support Agent',
            self::Customer => 'Customer',
        };
    }
}
