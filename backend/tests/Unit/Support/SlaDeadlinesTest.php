<?php

namespace Tests\Unit\Support;

use App\Enums\SlaStatus;
use App\Support\Sla\SlaDeadlines;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class SlaDeadlinesTest extends TestCase
{
    private SlaDeadlines $deadlines;

    protected function setUp(): void
    {
        parent::setUp();

        $this->deadlines = new SlaDeadlines(
            approachingAt: CarbonImmutable::parse('2026-10-12 11:00'),
            dueAt: CarbonImmutable::parse('2026-10-12 12:00'),
        );
    }

    public function test_it_is_within_sla_before_the_approaching_moment(): void
    {
        $this->assertSame(SlaStatus::Within, $this->deadlines->statusAt(CarbonImmutable::parse('2026-10-12 10:59')));
    }

    public function test_it_is_approaching_from_the_approaching_moment_until_due(): void
    {
        $this->assertSame(SlaStatus::Approaching, $this->deadlines->statusAt(CarbonImmutable::parse('2026-10-12 11:00')));
        $this->assertSame(SlaStatus::Approaching, $this->deadlines->statusAt(CarbonImmutable::parse('2026-10-12 11:59')));
    }

    public function test_it_is_breached_from_the_due_moment(): void
    {
        $this->assertSame(SlaStatus::Breached, $this->deadlines->statusAt(CarbonImmutable::parse('2026-10-12 12:00')));
        $this->assertSame(SlaStatus::Breached, $this->deadlines->statusAt(CarbonImmutable::parse('2026-10-13 09:00')));
    }
}
