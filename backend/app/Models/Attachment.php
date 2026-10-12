<?php

namespace App\Models;

use Database\Factories\AttachmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use LogicException;

#[Fillable(['uploaded_by', 'disk', 'path', 'original_name', 'mime_type', 'size'])]
class Attachment extends Model
{
    /** @use HasFactory<AttachmentFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }

    /** @return MorphTo<Model, $this> */
    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return BelongsTo<User, $this> */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function ticket(): Ticket
    {
        $attachable = $this->attachable;

        return match (true) {
            $attachable instanceof Ticket => $attachable,
            $attachable instanceof TicketReply => $attachable->ticket,
            default => throw new LogicException("Attachment {$this->id} is not linked to a ticket."),
        };
    }

    public function isInternal(): bool
    {
        return $this->attachable instanceof TicketReply && $this->attachable->is_internal;
    }
}
