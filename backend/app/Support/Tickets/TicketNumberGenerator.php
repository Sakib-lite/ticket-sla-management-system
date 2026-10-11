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
        if (DB::transactionLevel() === 0) {
            throw new LogicException('Ticket numbers must be generated inside a database transaction.');
        }

        $year = ($at ?? now())->year;

        $sequence = $this->lockSequence($year) ?? $this->startSequence($year);
        $sequence->increment('last_number');

        return $this->format($year, $sequence->last_number);
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
