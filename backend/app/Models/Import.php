<?php

namespace App\Models;

use App\Enums\ImportStatus;
use Database\Factories\ImportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'original_name',
    'disk',
    'path',
    'status',
    'total_rows',
    'processed_rows',
    'imported_rows',
    'skipped_rows',
    'failed_rows',
    'error_message',
    'started_at',
    'finished_at',
])]
class Import extends Model
{
    /** @use HasFactory<ImportFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => ImportStatus::class,
            'total_rows' => 'integer',
            'processed_rows' => 'integer',
            'imported_rows' => 'integer',
            'skipped_rows' => 'integer',
            'failed_rows' => 'integer',
            'started_at' => 'immutable_datetime',
            'finished_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<ImportError, $this> */
    public function errors(): HasMany
    {
        return $this->hasMany(ImportError::class);
    }

    public function progress(): ?int
    {
        if ($this->total_rows === null || $this->total_rows === 0) {
            return $this->status->isFinished() ? 100 : null;
        }

        return (int) min(100, round($this->processed_rows / $this->total_rows * 100));
    }

    public function isOwnedBy(User $user): bool
    {
        return $this->user_id === $user->id;
    }
}
