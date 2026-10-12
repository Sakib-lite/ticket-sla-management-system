<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AgentResource;
use App\Models\User;
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
            ->withCount('activeAssignedTickets as active_tickets_count')
            ->orderBy('name')
            ->get();

        return AgentResource::collection($agents);
    }
}
