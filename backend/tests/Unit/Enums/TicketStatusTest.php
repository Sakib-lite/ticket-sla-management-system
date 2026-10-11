<?php

namespace Tests\Unit\Enums;

use App\Enums\TicketStatus;
use PHPUnit\Framework\TestCase;

class TicketStatusTest extends TestCase
{
    public function test_closed_is_final(): void
    {
        $this->assertSame([], TicketStatus::Closed->transitions());
        $this->assertFalse(TicketStatus::Closed->canTransitionTo(TicketStatus::Open));
    }

    public function test_a_resolved_ticket_can_be_reopened_or_closed_only(): void
    {
        $this->assertTrue(TicketStatus::Resolved->canTransitionTo(TicketStatus::Open));
        $this->assertTrue(TicketStatus::Resolved->canTransitionTo(TicketStatus::Closed));
        $this->assertFalse(TicketStatus::Resolved->canTransitionTo(TicketStatus::InProgress));
    }

    public function test_a_status_never_transitions_to_itself(): void
    {
        foreach (TicketStatus::cases() as $status) {
            $this->assertFalse($status->canTransitionTo($status), "{$status->value} transitions to itself");
        }
    }

    public function test_active_and_finished_partition_all_statuses(): void
    {
        $this->assertEqualsCanonicalizing(
            TicketStatus::cases(),
            [...TicketStatus::active(), ...TicketStatus::finished()]
        );
        $this->assertSame([TicketStatus::Resolved, TicketStatus::Closed], TicketStatus::finished());
    }
}
