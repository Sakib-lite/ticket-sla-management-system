<?php

namespace Database\Factories;

use App\Enums\SlaStatus;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Category;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Ticket> */
class TicketFactory extends Factory
{
    public function definition(): array
    {
        return [
            'ticket_number' => sprintf('TKT-%d-%06d', now()->year, fake()->unique()->numberBetween(1, 999999)),
            'subject' => rtrim(fake()->sentence(6), '.'),
            'description' => fake()->paragraphs(2, true),
            'priority' => fake()->randomElement(TicketPriority::cases()),
            'status' => TicketStatus::Open,
            'category_id' => Category::factory(),
            'created_by' => User::factory(),
            'assigned_agent_id' => null,
            'sla_due_at' => now()->addHours(24),
            'sla_status' => SlaStatus::Within,
        ];
    }

    public function priority(TicketPriority $priority): static
    {
        return $this->state(fn () => ['priority' => $priority]);
    }

    public function critical(): static
    {
        return $this->priority(TicketPriority::Critical);
    }

    public function status(TicketStatus $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }

    public function inProgress(): static
    {
        return $this->status(TicketStatus::InProgress);
    }

    public function resolved(bool $withinSla = true): static
    {
        return $this->state(fn () => [
            'status' => TicketStatus::Resolved,
            'resolved_at' => now(),
            'resolved_within_sla' => $withinSla,
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn () => [
            'status' => TicketStatus::Closed,
            'resolved_at' => now()->subHour(),
            'closed_at' => now(),
            'resolved_within_sla' => true,
        ]);
    }

    public function createdBy(User $customer): static
    {
        return $this->state(fn () => ['created_by' => $customer->id]);
    }

    public function assignedTo(User $agent): static
    {
        return $this->state(fn () => [
            'assigned_agent_id' => $agent->id,
            'status' => TicketStatus::InProgress,
        ]);
    }

    public function approachingSla(): static
    {
        return $this->state(fn () => [
            'sla_status' => SlaStatus::Approaching,
            'sla_due_at' => now()->addMinutes(30),
        ]);
    }

    public function breached(): static
    {
        return $this->state(fn () => [
            'sla_status' => SlaStatus::Breached,
            'sla_due_at' => now()->subHour(),
            'escalated_at' => now()->subHour(),
        ]);
    }
}
