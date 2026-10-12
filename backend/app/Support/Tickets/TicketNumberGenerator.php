<?php

namespace App\Support\Tickets;

use App\Models\TicketSequence;
use Carbon\CarbonInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use LogicException;

final readonly class TicketNumberGenerator
{
    public function __construct(
        private string $prefix,
        private int $minimumDigits,
    ) {}

    public function next(?CarbonInterface $at = null): string
    {
        return $this->nextBatch(1, $at)[0];
    }

    /** @return list<string> */
    public function nextBatch(int $count, ?CarbonInterface $at = null): array
    {
        if (DB::transactionLevel() === 0) {
            throw new LogicException('Ticket numbers must be generated inside a database transaction.');
        }

        if ($count < 1) {
            return [];
        }

        $year = ($at ?? now())->year;

        $sequence = $this->lockSequence($year) ?? $this->startSequence($year);
        $first = $sequence->last_number + 1;
        $sequence->increment('last_number', $count);

        return array_map(fn (int $number) => $this->format($year, $number), range($first, $first + $count - 1));
    }

    public function format(int $year, int $number): string
    {
        return sprintf('%s-%d-%0'.$this->minimumDigits.'d', $this->prefix, $year, $number);
    }

    private function lockSequence(int $year): ?TicketSequence
    {
        return TicketSequence::query()->where('year', $year)->lockForUpdate()->first();
    }

    private function startSequence(int $year): TicketSequence
    {
        try {
            return TicketSequence::create(['year' => $year, 'last_number' => 0]);
        } catch (UniqueConstraintViolationException) {
            return $this->lockSequence($year);
        }
    }
}
