<?php

namespace App\Models;

use App\Enums\ActivityType;
use Database\Factories\TicketActivityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['ticket_id', 'actor_id', 'type', 'payload', 'created_at'])]
class TicketActivity extends Model
{
    /** @use HasFactory<TicketActivityFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'type' => ActivityType::class,
            'payload' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Ticket, $this> */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /** @param  Builder<TicketActivity>  $query */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        if ($user->isCustomer()) {
            $internal = array_filter(ActivityType::cases(), fn (ActivityType $type) => $type->isInternal());
            $query->whereNotIn('type', array_values($internal));
        }
    }
}
