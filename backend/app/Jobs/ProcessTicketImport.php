<?php

namespace App\Jobs;

use App\Enums\ImportStatus;
use App\Models\Import;
use App\Services\Imports\TicketImporter;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ProcessTicketImport implements ShouldQueue
{
    use Queueable;

    public int $timeout = 900;

    public int $tries = 1;

    public function __construct(public readonly Import $import) {}

    public function handle(TicketImporter $importer): void
    {
        $importer->import($this->import);
    }

    public function failed(?Throwable $exception): void
    {
        $this->import->update([
            'status' => ImportStatus::Failed,
            'error_message' => $exception?->getMessage() ?? 'The import failed unexpectedly.',
            'finished_at' => now(),
        ]);
    }
}
