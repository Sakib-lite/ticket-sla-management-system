<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityResource;
use App\Models\Ticket;
use App\Models\TicketActivity;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TicketActivityController extends Controller
{
    use AuthorizesRequests;

    private const DEFAULT_PER_PAGE = 25;

    public function index(Request $request, Ticket $ticket): AnonymousResourceCollection
    {
        $this->authorize('viewAny', [TicketActivity::class, $ticket]);

        $validated = $request->validate([
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ]);

        $activities = $ticket->activities()
            ->visibleTo($request->user())
            ->with('actor')
            ->latest('created_at')
            ->latest('id')
            ->paginate($validated['per_page'] ?? self::DEFAULT_PER_PAGE);

        return ActivityResource::collection($activities);
    }
}
