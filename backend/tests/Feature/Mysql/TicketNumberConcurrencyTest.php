<?php

namespace Tests\Feature\Mysql;

use App\Models\TicketSequence;
use App\Support\Tickets\TicketNumberGenerator;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;

#[Group('mysql')]
class TicketNumberConcurrencyTest extends MysqlTestCase
{
    private const YEAR = 2099;

    public function test_parallel_requests_never_receive_the_same_ticket_number(): void
    {
        $workers = 8;
        $numbersPerWorker = 5;
        $at = CarbonImmutable::create(self::YEAR, 1, 1);

        TicketSequence::where('year', self::YEAR)->delete();

        try {
            $batches = $this->runInParallel($workers, function () use ($numbersPerWorker, $at) {
                $generator = app(TicketNumberGenerator::class);
                $numbers = [];

                for ($i = 0; $i < $numbersPerWorker; $i++) {
                    $numbers[] = DB::transaction(fn () => $generator->next($at), attempts: 3);
                }

                return $numbers;
            });

            $numbers = array_merge(...$batches);

            $this->assertCount($workers * $numbersPerWorker, $numbers);
            $this->assertCount($workers * $numbersPerWorker, array_unique($numbers));
            $this->assertSame($workers * $numbersPerWorker, TicketSequence::where('year', self::YEAR)->value('last_number'));
        } finally {
            TicketSequence::where('year', self::YEAR)->delete();
        }
    }
}
