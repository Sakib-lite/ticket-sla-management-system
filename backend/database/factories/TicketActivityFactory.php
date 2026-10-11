<?php

namespace Database\Factories;

use App\Enums\ActivityType;
use App\Models\Ticket;
use App\Models\TicketActivity;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TicketActivity>
 */
class TicketActivityFactory extends Factory
{
    public function definition(): array
    {
        return [
            'ticket_id' => Ticket::factory(),
            'actor_id' => User::factory(),
            'type' => ActivityType::Created,
            'payload' => null,
            'created_at' => now(),
        ];
    }

    public function type(ActivityType $type, ?array $payload = null): static
    {
        return $this->state(fn () => ['type' => $type, 'payload' => $payload]);
    }

    public function bySystem(): static
    {
        return $this->state(fn () => ['actor_id' => null]);
    }
}
