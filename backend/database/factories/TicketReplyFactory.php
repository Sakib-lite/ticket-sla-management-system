<?php

namespace Database\Factories;

use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TicketReply>
 */
class TicketReplyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'ticket_id' => Ticket::factory(),
            'author_id' => User::factory(),
            'body' => fake()->paragraph(),
            'is_internal' => false,
        ];
    }

    public function internal(): static
    {
        return $this->state(fn () => ['is_internal' => true]);
    }

    public function by(User $author): static
    {
        return $this->state(fn () => ['author_id' => $author->id]);
    }
}
