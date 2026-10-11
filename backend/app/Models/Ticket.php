<?php

namespace App\Models;

use App\Enums\SlaStatus;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Database\Factories\TicketFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable([
    'ticket_number',
    'subject',
    'description',
    'priority',
    'status',
    'category_id',
    'created_by',
    'assigned_agent_id',
    'sla_due_at',
    'sla_status',
    'escalated_at',
    'resolved_at',
    'closed_at',
    'resolved_within_sla',
    'external_reference',
])]
class Ticket extends Model
{
    /** @use HasFactory<TicketFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'priority' => TicketPriority::class,
            'status' => TicketStatus::class,
            'sla_status' => SlaStatus::class,
            'sla_due_at' => 'datetime',
            'escalated_at' => 'datetime',
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
            'resolved_within_sla' => 'boolean',
        ];
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, $this> */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_agent_id');
    }

    /** @return HasMany<TicketReply, $this> */
    public function replies(): HasMany
    {
        return $this->hasMany(TicketReply::class);
    }

    /** @return HasMany<TicketActivity, $this> */
    public function activities(): HasMany
    {
        return $this->hasMany(TicketActivity::class);
    }

    /** @return MorphMany<Attachment, $this> */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function isAssigned(): bool
    {
        return $this->assigned_agent_id !== null;
    }

    public function isEscalated(): bool
    {
        return $this->escalated_at !== null;
    }

    /** @param  Builder<Ticket>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->whereIn('status', TicketStatus::active());
    }

    /** @param  Builder<Ticket>  $query */
    public function scopeFinished(Builder $query): void
    {
        $query->whereIn('status', TicketStatus::finished());
    }

    /** @param  Builder<Ticket>  $query */
    public function scopeUnassigned(Builder $query): void
    {
        $query->whereNull('assigned_agent_id');
    }
}
