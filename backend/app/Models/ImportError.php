<?php

namespace App\Models;

use App\Enums\ImportErrorKind;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['import_id', 'row_number', 'kind', 'messages', 'raw', 'created_at'])]
class ImportError extends Model
{
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'row_number' => 'integer',
            'kind' => ImportErrorKind::class,
            'messages' => 'array',
            'raw' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Import, $this> */
    public function import(): BelongsTo
    {
        return $this->belongsTo(Import::class);
    }
}
