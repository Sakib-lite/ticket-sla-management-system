<?php

namespace App\Services\Replies;

use App\Enums\ActivityType;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\User;
use App\Services\ActivityRecorder;
use App\Services\Tickets\TicketService;
use Illuminate\Support\Facades\DB;

final readonly class ReplyService
{
    public function __construct(
        private ActivityRecorder $activities,
        private TicketService $tickets,
    ) {}

    public function add(Ticket $ticket, User $author, string $body, bool $internal): TicketReply
    {
        if ($ticket->status === TicketStatus::Closed) {
            throw new TicketClosedException($ticket);
        }

        return DB::transaction(function () use ($ticket, $author, $body, $internal) {
            $reply = $ticket->replies()->create([
                'author_id' => $author->id,
                'body' => $body,
                'is_internal' => $internal,
            ]);

            $this->activities->record(
                $ticket,
                $internal ? ActivityType::NoteAdded : ActivityType::ReplyAdded,
                $author,
                ['reply_id' => $reply->id],
            );

            if (! $internal) {
                $this->followUpStatus($ticket, $author);
            }

            return $reply;
        });
    }

    private function followUpStatus(Ticket $ticket, User $author): void
    {
        $next = match (true) {
            $author->isCustomer() && $this->awaitsCustomer($ticket) => TicketStatus::Open,
            $author->isStaff() && $this->awaitsStaff($ticket) => TicketStatus::WaitingForCustomer,
            default => null,
        };

        if ($next !== null) {
            $this->tickets->changeStatus($ticket, $author, $next);
        }
    }

    private function awaitsCustomer(Ticket $ticket): bool
    {
        return in_array($ticket->status, [TicketStatus::WaitingForCustomer, TicketStatus::Resolved], true);
    }

    private function awaitsStaff(Ticket $ticket): bool
    {
        return in_array($ticket->status, [TicketStatus::Open, TicketStatus::InProgress], true);
    }
}
