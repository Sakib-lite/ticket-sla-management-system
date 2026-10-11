<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexTicketsRequest;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use App\Services\Tickets\TicketService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TicketController extends Controller
{
    use AuthorizesRequests;

    private const RELATIONS = ['category', 'creator', 'assignee'];

    private const DETAIL_RELATIONS = [...self::RELATIONS, 'attachments'];

    public function __construct(private readonly TicketService $tickets) {}

    public function index(IndexTicketsRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Ticket::class);

        $query = Ticket::query()
            ->visibleTo($request->user())
            ->with(self::RELATIONS);

        $tickets = $request->filters()->apply($query)->paginate($request->perPage());

        return TicketResource::collection($tickets);
    }

    public function store(StoreTicketRequest $request): TicketResource
    {
        $ticket = $this->tickets->create(
            $request->user(),
            $request->customer(),
            $request->ticketAttributes(),
            $request->files(),
        );

        return TicketResource::make($ticket->load(self::DETAIL_RELATIONS));
    }

    public function show(Ticket $ticket): TicketResource
    {
        $this->authorize('view', $ticket);

        return TicketResource::make($ticket->load(self::DETAIL_RELATIONS));
    }

    public function update(UpdateTicketRequest $request, Ticket $ticket): TicketResource
    {
        $ticket = $this->tickets->update($ticket, $request->user(), $request->validated());

        return TicketResource::make($ticket->load(self::DETAIL_RELATIONS));
    }
}
