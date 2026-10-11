<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AgentResource;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AgentController extends Controller
{
    use AuthorizesRequests;

    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAgents', User::class);

        $agents = User::query()
            ->agents()
            ->withCount(['assignedTickets as active_tickets_count' => fn (Builder $query) => $query->active()])
            ->orderBy('name')
            ->get();

        return AgentResource::collection($agents);
    }
}
