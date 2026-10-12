<?php

namespace App\Http\Resources;

use App\Models\Import;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Import */
class ImportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'original_name' => $this->original_name,
            'status' => $this->status->value,
            'progress' => $this->progress(),
            'total_rows' => $this->total_rows,
            'processed_rows' => $this->processed_rows,
            'imported_rows' => $this->imported_rows,
            'skipped_rows' => $this->skipped_rows,
            'failed_rows' => $this->failed_rows,
            'error_message' => $this->error_message,
            'user' => UserResource::make($this->whenLoaded('user')),
            'started_at' => $this->started_at,
            'finished_at' => $this->finished_at,
            'created_at' => $this->created_at,
        ];
    }
}
