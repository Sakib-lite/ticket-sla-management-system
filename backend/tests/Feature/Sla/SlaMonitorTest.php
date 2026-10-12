<?php

namespace Tests\Feature\Sla;

use App\Enums\ActivityType;
use App\Enums\SlaStatus;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\SlaApproachingNotification;
use App\Notifications\SlaBreachedNotification;
use App\Services\Sla\SlaMonitor;
use Carbon\CarbonImmutable;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SlaMonitorTest extends TestCase
{
    private CarbonImmutable $createdAt;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->createdAt = CarbonImmutable::parse('2026-10-12 09:00:00');
        CarbonImmutable::setTestNow($this->createdAt);
    }

    public function test_a_ticket_moves_through_within_approaching_and_breached_exactly_once(): void
    {
        $agent = User::factory()->agent()->create();
        $admin = User::factory()->administrator()->create();
        $ticket = $this->ticketDueIn(hours: 2)->assignedTo($agent)->create();
        $monitor = app(SlaMonitor::class);

        $quiet = $monitor->run($this->createdAt->addMinutes(30));
        $this->assertSame([0, 0], [$quiet->approaching, $quiet->breached]);
        $this->assertSame(SlaStatus::Within, $ticket->refresh()->sla_status);

        $approaching = $monitor->run($this->createdAt->addMinutes(100));
        $this->assertSame([1, 0], [$approaching->approaching, $approaching->breached]);
        $this->assertSame(SlaStatus::Approaching, $ticket->refresh()->sla_status);
        Notification::assertSentTo([$agent, $admin], SlaApproachingNotification::class);

        $again = $monitor->run($this->createdAt->addMinutes(110));
        $this->assertSame([0, 0], [$again->approaching, $again->breached]);

        $breached = $monitor->run($this->createdAt->addMinutes(121));
        $this->assertSame([0, 1], [$breached->approaching, $breached->breached]);
        $ticket->refresh();
        $this->assertSame(SlaStatus::Breached, $ticket->sla_status);
        $this->assertTrue($ticket->isEscalated());
        $this->assertTrue($ticket->escalated_at->equalTo($this->createdAt->addMinutes(121)));
        Notification::assertSentTo([$agent, $admin], SlaBreachedNotification::class);

        $later = $monitor->run($this->createdAt->addHours(5));
        $this->assertSame([0, 0], [$later->approaching, $later->breached]);
        $this->assertSame(1, $ticket->activities()->where('type', ActivityType::SlaApproaching)->count());
        $this->assertSame(1, $ticket->activities()->where('type', ActivityType::Escalated)->count());
        Notification::assertSentToTimes($agent, SlaBreachedNotification::class, 1);
    }

    public function test_a_ticket_that_is_already_overdue_escalates_without_a_separate_approaching_step(): void
    {
        $ticket = $this->ticketDueIn(hours: 2)->create();

        $report = app(SlaMonitor::class)->run($this->createdAt->addHours(3));

        $this->assertSame([0, 1], [$report->approaching, $report->breached]);
        $this->assertSame(SlaStatus::Breached, $ticket->refresh()->sla_status);
        $this->assertSame(0, $ticket->activities()->where('type', ActivityType::SlaApproaching)->count());
    }

    public function test_resolved_and_closed_tickets_are_left_alone(): void
    {
        $resolved = $this->ticketDueIn(hours: 1)->resolved()->create(['sla_status' => SlaStatus::Within]);
        $closed = $this->ticketDueIn(hours: 1)->closed()->create(['sla_status' => SlaStatus::Within]);

        $report = app(SlaMonitor::class)->run($this->createdAt->addDays(2));

        $this->assertSame([0, 0], [$report->approaching, $report->breached]);
        $this->assertSame(SlaStatus::Within, $resolved->refresh()->sla_status);
        $this->assertSame(SlaStatus::Within, $closed->refresh()->sla_status);
        Notification::assertNothingSent();
    }

    public function test_unassigned_breaches_go_to_the_administrators(): void
    {
        $admins = User::factory()->administrator()->count(2)->create();
        User::factory()->agent()->create();
        $this->ticketDueIn(hours: 1)->create();

        app(SlaMonitor::class)->run($this->createdAt->addHours(2));

        Notification::assertSentTo($admins, SlaBreachedNotification::class);
        Notification::assertCount(2);
    }

    public function test_the_artisan_command_reports_what_it_did(): void
    {
        $this->ticketDueIn(hours: 1)->create();
        $this->ticketDueIn(hours: 8)->create();
        CarbonImmutable::setTestNow($this->createdAt->addMinutes(90));

        $this->artisan('sla:monitor')
            ->expectsOutput('Approaching: 0, escalated: 1.')
            ->assertSuccessful();

        CarbonImmutable::setTestNow($this->createdAt->addHours(7));

        $this->artisan('sla:monitor')
            ->expectsOutput('Approaching: 1, escalated: 0.')
            ->assertSuccessful();
    }

    public function test_the_monitor_is_scheduled_every_minute(): void
    {
        $event = collect(app(Schedule::class)->events())
            ->first(fn ($event) => str_contains($event->command, 'sla:monitor'));

        $this->assertNotNull($event);
        $this->assertSame('* * * * *', $event->expression);
        $this->assertTrue($event->withoutOverlapping);
    }

    private function ticketDueIn(int $hours)
    {
        return Ticket::factory()->state([
            'created_at' => $this->createdAt,
            'sla_status' => SlaStatus::Within,
            'sla_approaching_at' => $this->createdAt->addMinutes((int) ($hours * 60 * 0.8)),
            'sla_due_at' => $this->createdAt->addHours($hours),
        ]);
    }
}
