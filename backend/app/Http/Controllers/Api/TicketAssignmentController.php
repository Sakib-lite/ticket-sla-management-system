<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssignTicketRequest;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use App\Services\Assignment\AssignmentService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class TicketAssignmentController extends Controller
{
    use AuthorizesRequests;

    private const RELATIONS = ['category', 'creator', 'assignee'];

    public function __construct(private readonly AssignmentService $assignments) {}

    public function assign(AssignTicketRequest $request, Ticket $ticket): TicketResource
    {
        $ticket = $this->assignments->assign($ticket, $request->agent(), $request->user());

        return TicketResource::make($ticket->load(self::RELATIONS));
    }

    public function claim(Request $request, Ticket $ticket): TicketResource
    {
        $this->authorize('claim', $ticket);

        $ticket = $this->assignments->claim($ticket, $request->user());

        return TicketResource::make($ticket->load(self::RELATIONS));
    }

    public function unassign(Request $request, Ticket $ticket): TicketResource
    {
        $this->authorize('assign', $ticket);

        $ticket = $this->assignments->unassign($ticket, $request->user());

        return TicketResource::make($ticket->load(self::RELATIONS));
    }
}
