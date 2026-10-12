<?php

namespace App\Services\Sla;

use App\Enums\ActivityType;
use App\Enums\SlaStatus;
use App\Events\SlaApproaching;
use App\Events\SlaBreached;
use App\Models\Ticket;
use App\Services\ActivityRecorder;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

final readonly class SlaMonitor
{
    private const CHUNK = 200;

    public function __construct(private ActivityRecorder $activities) {}

    public function run(?CarbonInterface $now = null): SlaMonitorReport
    {
        $now = CarbonImmutable::instance($now ?? now());
        $approaching = 0;
        $breached = 0;

        Ticket::query()
            ->active()
            ->where('sla_status', SlaStatus::Within)
            ->where('sla_approaching_at', '<=', $now)
            ->where('sla_due_at', '>', $now)
            ->chunkById(self::CHUNK, function (Collection $tickets) use (&$approaching) {
                $tickets->each(fn (Ticket $ticket) => $this->markApproaching($ticket));
                $approaching += $tickets->count();
            });

        Ticket::query()
            ->active()
            ->where('sla_status', '!=', SlaStatus::Breached)
            ->where('sla_due_at', '<=', $now)
            ->chunkById(self::CHUNK, function (Collection $tickets) use (&$breached, $now) {
                $tickets->each(fn (Ticket $ticket) => $this->escalate($ticket, $now));
                $breached += $tickets->count();
            });

        return new SlaMonitorReport($approaching, $breached);
    }

    private function markApproaching(Ticket $ticket): void
    {
        DB::transaction(function () use ($ticket) {
            $ticket->update(['sla_status' => SlaStatus::Approaching]);
            $this->activities->record($ticket, ActivityType::SlaApproaching, null, [
                'due_at' => $ticket->sla_due_at->toIso8601String(),
            ]);
        });

        SlaApproaching::dispatch($ticket);
    }

    private function escalate(Ticket $ticket, CarbonImmutable $now): void
    {
        DB::transaction(function () use ($ticket, $now) {
            $ticket->update(['sla_status' => SlaStatus::Breached, 'escalated_at' => $now]);
            $this->activities->record($ticket, ActivityType::Escalated, null, [
                'due_at' => $ticket->sla_due_at->toIso8601String(),
            ]);
        });

        SlaBreached::dispatch($ticket);
    }
}
