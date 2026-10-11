<?php

namespace Tests\Feature\Support;

use App\Models\TicketSequence;
use App\Support\Tickets\TicketNumberGenerator;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TicketNumberGeneratorTest extends TestCase
{
    private TicketNumberGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->generator = app(TicketNumberGenerator::class);
    }

    public function test_numbers_are_sequential_and_zero_padded(): void
    {
        $at = CarbonImmutable::parse('2026-03-01');

        $first = DB::transaction(fn () => $this->generator->next($at));
        $second = DB::transaction(fn () => $this->generator->next($at));

        $this->assertSame('TKT-2026-000001', $first);
        $this->assertSame('TKT-2026-000002', $second);
    }

    public function test_each_year_has_its_own_sequence(): void
    {
        DB::transaction(fn () => $this->generator->next(CarbonImmutable::parse('2026-12-31 23:59')));

        $newYear = DB::transaction(fn () => $this->generator->next(CarbonImmutable::parse('2027-01-01 00:00')));

        $this->assertSame('TKT-2027-000001', $newYear);
        $this->assertSame(2, TicketSequence::count());
    }

    public function test_padding_is_a_minimum_so_numbering_never_runs_out(): void
    {
        TicketSequence::create(['year' => 2026, 'last_number' => 999999]);

        $number = DB::transaction(fn () => $this->generator->next(CarbonImmutable::parse('2026-06-01')));

        $this->assertSame('TKT-2026-1000000', $number);
    }

    public function test_the_prefix_comes_from_configuration(): void
    {
        $generator = new TicketNumberGenerator(prefix: 'HELP', minimumDigits: 4);

        $this->assertSame('HELP-2026-0042', $generator->format(2026, 42));
    }
}
