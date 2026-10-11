<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReplyRequest;
use App\Http\Resources\ReplyResource;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Services\Replies\ReplyService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TicketReplyController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private readonly ReplyService $replies) {}

    public function index(Request $request, Ticket $ticket): AnonymousResourceCollection
    {
        $this->authorize('viewAny', [TicketReply::class, $ticket]);

        $replies = $ticket->replies()
            ->visibleTo($request->user())
            ->with('author')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        return ReplyResource::collection($replies);
    }

    public function store(StoreReplyRequest $request, Ticket $ticket): ReplyResource
    {
        $reply = $this->replies->add(
            $ticket,
            $request->user(),
            $request->validated('body'),
            $request->isInternal(),
        );

        return ReplyResource::make($reply->load('author'));
    }
}
