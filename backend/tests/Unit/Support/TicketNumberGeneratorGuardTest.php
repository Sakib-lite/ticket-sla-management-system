<?php

namespace Tests\Unit\Support;

use App\Support\Tickets\TicketNumberGenerator;
use Illuminate\Foundation\Testing\TestCase;
use LogicException;

class TicketNumberGeneratorGuardTest extends TestCase
{
    public function test_it_refuses_to_run_outside_a_transaction(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('inside a database transaction');

        app(TicketNumberGenerator::class)->next();
    }
}
