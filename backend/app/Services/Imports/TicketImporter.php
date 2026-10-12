<?php

namespace App\Services\Imports;

use App\Enums\ActivityType;
use App\Enums\ImportStatus;
use App\Enums\SlaStatus;
use App\Enums\TicketStatus;
use App\Models\Import;
use App\Models\ImportError;
use App\Models\Ticket;
use App\Models\TicketActivity;
use App\Support\Dashboard\DashboardCache;
use App\Support\Imports\ImportChunkOutcome;
use App\Support\Imports\ImportRowValidator;
use App\Support\Imports\TicketCsvReader;
use App\Support\Sla\SlaCalculator;
use App\Support\Tickets\TicketNumberGenerator;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\LazyCollection;

final readonly class TicketImporter
{
    private const CHUNK = 500;

    public function __construct(
        private TicketNumberGenerator $ticketNumbers,
        private SlaCalculator $sla,
        private ImportRowValidator $validator,
        private DashboardCache $dashboardCache,
    ) {}

    public function import(Import $import): void
    {
        $reader = new TicketCsvReader(Storage::disk($import->disk)->path($import->path));

        $import->update([
            'status' => ImportStatus::Processing,
            'started_at' => now(),
            'total_rows' => $reader->countRows(),
        ]);

        $seenReferences = [];

        $reader->rows()
            ->chunk(self::CHUNK)
            ->each(function (LazyCollection $chunk) use ($import, &$seenReferences) {
                $this->processChunk($import, $chunk->values()->all(), $seenReferences);
            });

        $import->update(['status' => ImportStatus::Completed, 'finished_at' => now()]);

        $this->dashboardCache->bump();
    }

    /**
     * @param  list<array{row: int, data: array<string, string>}>  $rows
     * @param  array<string, true>  $seenReferences
     */
    private function processChunk(Import $import, array $rows, array &$seenReferences): void
    {
        $outcome = $this->validator->validate($rows, $seenReferences);

        DB::transaction(function () use ($import, $rows, $outcome) {
            $this->insertTickets($import, $outcome);
            $this->insertErrors($import, $outcome);

            $import->update([
                'processed_rows' => $import->processed_rows + count($rows),
                'imported_rows' => $import->imported_rows + count($outcome->valid),
                'skipped_rows' => $import->skipped_rows + $outcome->skipped(),
                'failed_rows' => $import->failed_rows + $outcome->failed(),
            ]);
        }, attempts: 3);
    }

    private function insertTickets(Import $import, ImportChunkOutcome $outcome): void
    {
        if ($outcome->valid === []) {
            return;
        }

        $now = now();
        $numbers = $this->ticketNumbers->nextBatch(count($outcome->valid), $now);
        $rows = [];

        foreach ($outcome->valid as $index => $row) {
            $createdAt = $row['created_at'] ? CarbonImmutable::parse($row['created_at']) : $now;
            $deadlines = $this->sla->deadlines($row['priority'], $createdAt);

            $rows[] = [
                'ticket_number' => $numbers[$index],
                'external_reference' => $row['external_reference'],
                'subject' => $row['subject'],
                'description' => $row['description'],
                'priority' => $row['priority']->value,
                'status' => TicketStatus::Open->value,
                'category_id' => $row['category_id'],
                'created_by' => $row['created_by'],
                'sla_due_at' => $deadlines->dueAt,
                'sla_approaching_at' => $deadlines->approachingAt,
                'sla_status' => SlaStatus::Within->value,
                'created_at' => $createdAt,
                'updated_at' => $now,
            ];
        }

        Ticket::query()->insert($rows);

        $ticketIds = Ticket::query()->whereIn('ticket_number', $numbers)->pluck('id');

        TicketActivity::query()->insert($ticketIds->map(fn (int $ticketId) => [
            'ticket_id' => $ticketId,
            'actor_id' => $import->user_id,
            'type' => ActivityType::Imported->value,
            'payload' => json_encode(['import_id' => $import->id]),
            'created_at' => $now,
        ])->all());
    }

    private function insertErrors(Import $import, ImportChunkOutcome $outcome): void
    {
        if ($outcome->rejected === []) {
            return;
        }

        $now = now();

        ImportError::query()->insert(array_map(fn (array $row) => [
            'import_id' => $import->id,
            'row_number' => $row['row'],
            'kind' => $row['kind'],
            'messages' => json_encode($row['messages']),
            'raw' => json_encode($row['raw']),
            'created_at' => $now,
        ], $outcome->rejected));
    }
}
