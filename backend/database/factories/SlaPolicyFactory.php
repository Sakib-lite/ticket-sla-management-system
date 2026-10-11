<?php

namespace Database\Factories;

use App\Enums\TicketPriority;
use App\Models\SlaPolicy;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SlaPolicy>
 */
class SlaPolicyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'priority' => fake()->unique()->randomElement(TicketPriority::cases()),
            'resolution_hours' => fake()->randomElement([2, 8, 24, 48]),
            'approaching_threshold_percent' => 80,
        ];
    }

    public function forPriority(TicketPriority $priority, int $hours): static
    {
        return $this->state(fn () => [
            'priority' => $priority,
            'resolution_hours' => $hours,
        ]);
    }
}
