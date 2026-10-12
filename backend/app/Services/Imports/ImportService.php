<?php

namespace App\Services\Imports;

use App\Enums\ImportStatus;
use App\Jobs\ProcessTicketImport;
use App\Models\Import;
use App\Models\User;
use Illuminate\Http\UploadedFile;

final readonly class ImportService
{
    public function __construct(private string $disk) {}

    public function start(User $user, UploadedFile $file): Import
    {
        $import = Import::create([
            'user_id' => $user->id,
            'original_name' => $file->getClientOriginalName(),
            'disk' => $this->disk,
            'path' => $file->store('imports', $this->disk),
            'status' => ImportStatus::Pending,
        ]);

        ProcessTicketImport::dispatch($import);

        return $import;
    }
}
