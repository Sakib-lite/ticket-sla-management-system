<?php

namespace App\Models;

use Database\Factories\TicketReplyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable(['ticket_id', 'author_id', 'body', 'is_internal'])]
class TicketReply extends Model
{
    /** @use HasFactory<TicketReplyFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_internal' => 'boolean',
        ];
    }

    /** @return BelongsTo<Ticket, $this> */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /** @return MorphMany<Attachment, $this> */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /** @param  Builder<TicketReply>  $query */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        if ($user->isCustomer()) {
            $query->where('is_internal', false);
        }
    }
}
