<?php

namespace App\Support\Imports;

final readonly class ImportChunkOutcome
{
    /**
     * @param  list<array<string, mixed>>  $valid
     * @param  list<array{row: int, kind: string, messages: list<string>, raw: array<string, string>}>  $rejected
     */
    public function __construct(
        public array $valid,
        public array $rejected,
    ) {}

    public function skipped(): int
    {
        return count(array_filter($this->rejected, fn (array $row) => $row['kind'] === 'duplicate'));
    }

    public function failed(): int
    {
        return count($this->rejected) - $this->skipped();
    }
}
