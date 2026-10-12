<?php

namespace App\Http\Resources;

use App\Models\ImportError;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ImportError */
class ImportErrorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'row_number' => $this->row_number,
            'kind' => $this->kind->value,
            'messages' => $this->messages,
            'raw' => $this->raw,
        ];
    }
}
