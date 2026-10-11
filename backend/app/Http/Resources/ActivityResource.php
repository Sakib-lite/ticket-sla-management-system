<?php

namespace App\Http\Resources;

use App\Models\TicketActivity;
use App\Support\Activities\ActivityPresenter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TicketActivity */
class ActivityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'description' => app(ActivityPresenter::class)->describe($this->resource),
            'actor' => UserResource::make($this->whenLoaded('actor')),
            'payload' => $this->payload,
            'created_at' => $this->created_at,
        ];
    }
}
