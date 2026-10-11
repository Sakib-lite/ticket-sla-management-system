<?php

namespace Tests\Feature\Support;

use App\Enums\SlaStatus;
use App\Enums\TicketPriority;
use App\Models\SlaPolicy;
use App\Models\Ticket;
use App\Support\Sla\MissingSlaPolicyException;
use App\Support\Sla\SlaCalculator;
use Carbon\CarbonImmutable;
use Database\Seeders\SlaPolicySeeder;
use Tests\TestCase;

class SlaCalculatorTest extends TestCase
{
    private const TIMEZONE = 'Asia/Dhaka';

    private SlaCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SlaPolicySeeder::class);
        $this->calculator = app(SlaCalculator::class);
    }

    public function test_deadlines_follow_the_priority_policy_in_business_hours(): void
    {
        $thursdayFivePm = CarbonImmutable::parse('2026-10-08 17:00', self::TIMEZONE);

        $deadlines = $this->calculator->deadlines(TicketPriority::Critical, $thursdayFivePm);

        $this->assertSame('2026-10-11 10:00', $deadlines->dueAt->setTimezone(self::TIMEZONE)->format('Y-m-d H:i'));
        $this->assertSame('2026-10-11 09:36', $deadlines->approachingAt->setTimezone(self::TIMEZONE)->format('Y-m-d H:i'));
    }

    public function test_changing_a_policy_takes_effect_without_restarting(): void
    {
        $from = CarbonImmutable::parse('2026-10-12 09:00', self::TIMEZONE);

        $before = $this->calculator->deadlines(TicketPriority::Low, $from);
        SlaPolicy::where('priority', TicketPriority::Low)->first()->update(['resolution_hours' => 4]);
        $after = $this->calculator->deadlines(TicketPriority::Low, $from);

        $this->assertSame('2026-10-19 12:00', $before->dueAt->setTimezone(self::TIMEZONE)->format('Y-m-d H:i'));
        $this->assertSame('2026-10-12 13:00', $after->dueAt->setTimezone(self::TIMEZONE)->format('Y-m-d H:i'));
    }

    public function test_a_missing_policy_is_an_explicit_error(): void
    {
        SlaPolicy::where('priority', TicketPriority::High)->delete();

        $this->expectException(MissingSlaPolicyException::class);

        $this->calculator->deadlines(TicketPriority::High, CarbonImmutable::now());
    }

    public function test_status_is_derived_from_the_stored_deadlines_for_active_tickets(): void
    {
        $ticket = Ticket::factory()->approachingSla()->create(['sla_status' => SlaStatus::Within]);

        $this->assertSame(SlaStatus::Approaching, $ticket->liveSlaStatus());
        $this->assertSame(SlaStatus::Breached, $ticket->liveSlaStatus(now()->addHour()));
    }

    public function test_finished_tickets_keep_the_status_they_were_resolved_with(): void
    {
        $ticket = Ticket::factory()->resolved()->create([
            'sla_status' => SlaStatus::Within,
            'sla_due_at' => now()->subDay(),
            'sla_approaching_at' => now()->subDays(2),
        ]);

        $this->assertSame(SlaStatus::Within, $ticket->liveSlaStatus());
    }
}
