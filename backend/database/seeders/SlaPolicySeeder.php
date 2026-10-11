<?php

namespace Database\Seeders;

use App\Enums\TicketPriority;
use App\Models\SlaPolicy;
use Illuminate\Database\Seeder;

class SlaPolicySeeder extends Seeder
{
    public const RESOLUTION_HOURS = [
        TicketPriority::Low->value => 48,
        TicketPriority::Medium->value => 24,
        TicketPriority::High->value => 8,
        TicketPriority::Critical->value => 2,
    ];

    public function run(): void
    {
        foreach (self::RESOLUTION_HOURS as $priority => $hours) {
            SlaPolicy::firstOrCreate(
                ['priority' => $priority],
                ['resolution_hours' => $hours, 'approaching_threshold_percent' => 80]
            );
        }
    }
}
