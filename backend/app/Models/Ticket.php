<?php

namespace App\Models;

use App\Enums\SlaStatus;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Observers\TicketObserver;
use App\Support\Sla\SlaDeadlines;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Database\Factories\TicketFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
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
    'sla_approaching_at',
    'sla_status',
    'escalated_at',
    'resolved_at',
    'closed_at',
    'resolved_within_sla',
    'external_reference',
])]
#[ObservedBy(TicketObserver::class)]
class Ticket extends Model
{
    /** @use HasFactory<TicketFactory> */
    use HasFactory;

    private const NUMBER_PATTERN = '/^[A-Z]+-\d{4}-\d+$/i';

    protected function casts(): array
    {
        return [
            'priority' => TicketPriority::class,
            'status' => TicketStatus::class,
            'sla_status' => SlaStatus::class,
            'sla_due_at' => 'immutable_datetime',
            'sla_approaching_at' => 'immutable_datetime',
            'escalated_at' => 'immutable_datetime',
            'resolved_at' => 'immutable_datetime',
            'closed_at' => 'immutable_datetime',
            'resolved_within_sla' => 'boolean',
        ];
    }

    public function resolveRouteBinding($value, $field = null): ?self
    {
        if ($field === null && preg_match(self::NUMBER_PATTERN, (string) $value)) {
            return $this->where('ticket_number', strtoupper($value))->first();
        }

        return parent::resolveRouteBinding($value, $field);
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

    public function slaDeadlines(): SlaDeadlines
    {
        return new SlaDeadlines(
            approachingAt: $this->sla_approaching_at,
            dueAt: $this->sla_due_at,
        );
    }

    public function liveSlaStatus(?CarbonInterface $now = null): SlaStatus
    {
        if ($this->status->isFinished()) {
            return $this->sla_status;
        }

        return $this->slaDeadlines()->statusAt($now ?? CarbonImmutable::now());
    }

    public function isAssigned(): bool
    {
        return $this->assigned_agent_id !== null;
    }

    public function isEscalated(): bool
    {
        return $this->escalated_at !== null;
    }

    public function isOwnedBy(User $user): bool
    {
        return $this->created_by === $user->id;
    }

    public function isAssignedTo(User $user): bool
    {
        return $this->assigned_agent_id === $user->id;
    }

    /** @param  Builder<Ticket>  $query */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        if ($user->isCustomer()) {
            $query->where('created_by', $user->id);
        }
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
