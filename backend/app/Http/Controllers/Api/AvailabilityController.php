<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateAvailabilityRequest;
use App\Http\Resources\UserResource;

class AvailabilityController extends Controller
{
    public function update(UpdateAvailabilityRequest $request): UserResource
    {
        $request->user()->update(['is_available' => $request->boolean('is_available')]);

        return UserResource::make($request->user());
    }
}
