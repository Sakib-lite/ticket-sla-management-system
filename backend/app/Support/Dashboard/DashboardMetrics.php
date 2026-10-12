<?php

namespace App\Support\Dashboard;

use App\Enums\SlaStatus;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

final readonly class DashboardMetrics
{
    private const CACHE_SECONDS = 60;

    public function __construct(private DashboardCache $cache) {}

    /** @return array<string, mixed> */
    public function for(User $user): array
    {
        return Cache::remember($this->cache->key($user), self::CACHE_SECONDS, fn () => $this->build($user));
    }

    /** @return array<string, mixed> */
    private function build(User $user): array
    {
        $tickets = Ticket::query()->visibleTo($user);

        $data = [
            'metrics' => $this->headline(clone $tickets),
            'by_priority' => $this->byPriority(clone $tickets),
            'generated_at' => now(),
        ];

        if ($user->isStaff()) {
            $data['by_agent'] = $this->byAgent(clone $tickets);
        }

        return $data;
    }

    /**
     * @param  Builder<Ticket>  $tickets
     * @return array<string, int>
     */
    private function headline(Builder $tickets): array
    {
        $active = $this->activeCondition();
        $critical = TicketPriority::Critical->value;
        $breached = SlaStatus::Breached->value;
        $approaching = SlaStatus::Approaching->value;

        $row = $tickets->selectRaw(implode(', ', [
            $this->countWhere($active, 'open'),
            $this->countWhere("{$active} AND assigned_agent_id IS NULL", 'unassigned'),
            $this->countWhere("{$active} AND priority = '{$critical}'", 'critical'),
            $this->countWhere("{$active} AND sla_status = '{$breached}'", 'breached'),
            $this->countWhere("{$active} AND sla_status = '{$approaching}'", 'approaching'),
            $this->countWhere('resolved_at IS NOT NULL', 'resolved'),
            $this->countWhere('resolved_at >= ?', 'resolved_last_7_days'),
        ]), [now()->subDays(7)])->first();

        return array_map(intval(...), $row->getAttributes());
    }

    /**
     * @param  Builder<Ticket>  $tickets
     * @return list<array{priority: string, label: string, total: int}>
     */
    private function byPriority(Builder $tickets): array
    {
        $totals = $tickets->active()
            ->selectRaw('priority, COUNT(*) AS total')
            ->groupBy('priority')
            ->pluck('total', 'priority');

        return collect(TicketPriority::cases())
            ->sortByDesc(fn (TicketPriority $priority) => $priority->weight())
            ->values()
            ->map(fn (TicketPriority $priority) => [
                'priority' => $priority->value,
                'label' => $priority->label(),
                'total' => (int) ($totals[$priority->value] ?? 0),
            ])
            ->all();
    }

    /**
     * @param  Builder<Ticket>  $tickets
     * @return list<array{agent_id: int, agent_name: string, total: int, breached: int}>
     */
    private function byAgent(Builder $tickets): array
    {
        $breached = SlaStatus::Breached->value;

        return $tickets->active()
            ->whereNotNull('assigned_agent_id')
            ->join('users', 'users.id', '=', 'tickets.assigned_agent_id')
            ->selectRaw("users.id AS agent_id, users.name AS agent_name, COUNT(*) AS total, {$this->countWhere("tickets.sla_status = '{$breached}'", 'breached')}")
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total')
            ->orderBy('users.name')
            ->toBase()
            ->get()
            ->map(fn (object $row) => [
                'agent_id' => (int) $row->agent_id,
                'agent_name' => $row->agent_name,
                'total' => (int) $row->total,
                'breached' => (int) $row->breached,
            ])
            ->all();
    }

    private function activeCondition(): string
    {
        $statuses = collect(TicketStatus::active())
            ->map(fn (TicketStatus $status) => "'{$status->value}'")
            ->implode(', ');

        return "status IN ({$statuses})";
    }

    private function countWhere(string $condition, string $alias): string
    {
        return "SUM(CASE WHEN {$condition} THEN 1 ELSE 0 END) AS {$alias}";
    }
}
