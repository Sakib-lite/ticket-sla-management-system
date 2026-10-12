<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSlaPolicyRequest;
use App\Http\Resources\SlaPolicyResource;
use App\Models\SlaPolicy;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SlaPolicyController extends Controller
{
    use AuthorizesRequests;

    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', SlaPolicy::class);

        $policies = SlaPolicy::all()->sortByDesc(fn (SlaPolicy $policy) => $policy->priority->weight())->values();

        return SlaPolicyResource::collection($policies);
    }

    public function update(UpdateSlaPolicyRequest $request, SlaPolicy $slaPolicy): SlaPolicyResource
    {
        $slaPolicy->update($request->validated());

        return SlaPolicyResource::make($slaPolicy);
    }
}
