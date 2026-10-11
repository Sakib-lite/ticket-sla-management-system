<?php

namespace Tests\Unit\Enums;

use App\Enums\SlaStatus;
use PHPUnit\Framework\TestCase;

class SlaStatusTest extends TestCase
{
    public function test_sla_status_only_moves_forward(): void
    {
        $this->assertTrue(SlaStatus::Approaching->isAfter(SlaStatus::Within));
        $this->assertTrue(SlaStatus::Breached->isAfter(SlaStatus::Approaching));
        $this->assertFalse(SlaStatus::Within->isAfter(SlaStatus::Breached));
        $this->assertFalse(SlaStatus::Within->isAfter(SlaStatus::Within));
    }
}
