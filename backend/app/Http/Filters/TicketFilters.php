<?php

namespace App\Http\Filters;

use App\Enums\TicketPriority;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Builder;

final readonly class TicketFilters
{
    public const SORTABLE = ['created_at', 'sla_due_at', 'priority', 'updated_at'];

    /** @param  array<string, mixed>  $filters */
    public function __construct(private array $filters) {}

    /**
     * @param  Builder<Ticket>  $query
     * @return Builder<Ticket>
     */
    public function apply(Builder $query): Builder
    {
        return $query
            ->when($this->value('view') === 'active', fn (Builder $query) => $query->active())
            ->when($this->value('view') === 'resolved', fn (Builder $query) => $query->finished())
            ->when($this->value('status'), fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($this->value('priority'), fn (Builder $query, string $priority) => $query->where('priority', $priority))
            ->when($this->value('category_id'), fn (Builder $query, int $categoryId) => $query->where('category_id', $categoryId))
            ->when($this->value('created_by'), fn (Builder $query, int $customerId) => $query->where('created_by', $customerId))
            ->when($this->value('sla_status'), fn (Builder $query, string $slaStatus) => $query->where('sla_status', $slaStatus))
            ->when($this->value('assignee') === 'unassigned', fn (Builder $query) => $query->unassigned())
            ->when(is_numeric($this->value('assignee')), fn (Builder $query) => $query->where('assigned_agent_id', (int) $this->value('assignee')))
            ->when($this->value('created_from'), fn (Builder $query, string $from) => $query->where('created_at', '>=', $from))
            ->when($this->value('created_to'), fn (Builder $query, string $to) => $query->where('created_at', '<=', $to.' 23:59:59'))
            ->when($this->value('search'), fn (Builder $query, string $term) => $this->search($query, $term))
            ->tap(fn (Builder $query) => $this->sort($query));
    }

    /** @param  Builder<Ticket>  $query */
    private function search(Builder $query, string $term): void
    {
        $escaped = addcslashes(trim($term), '%_\\');

        $query->where(fn (Builder $query) => $query
            ->where('ticket_number', 'like', "%{$escaped}%")
            ->orWhere('subject', 'like', "%{$escaped}%"));
    }

    /** @param  Builder<Ticket>  $query */
    private function sort(Builder $query): void
    {
        $column = $this->value('sort') ?? 'created_at';
        $direction = $this->value('direction') ?? 'desc';

        if ($column === 'priority') {
            $query->orderByRaw($this->priorityOrderExpression().' '.$direction);
        } else {
            $query->orderBy($column, $direction);
        }

        $query->orderBy('id', $direction);
    }

    private function priorityOrderExpression(): string
    {
        $cases = collect(TicketPriority::cases())
            ->map(fn (TicketPriority $priority) => "WHEN '{$priority->value}' THEN {$priority->weight()}")
            ->implode(' ');

        return "CASE priority {$cases} ELSE 0 END";
    }

    private function value(string $key): mixed
    {
        return $this->filters[$key] ?? null;
    }
}
