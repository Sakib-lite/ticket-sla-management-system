<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangeTicketStatusRequest;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use App\Services\Tickets\TicketService;

class TicketStatusController extends Controller
{
    public function __construct(private readonly TicketService $tickets) {}

    public function __invoke(ChangeTicketStatusRequest $request, Ticket $ticket): TicketResource
    {
        $ticket = $this->tickets->changeStatus($ticket, $request->user(), $request->status());

        return TicketResource::make($ticket->load(['category', 'creator', 'assignee']));
    }
}
