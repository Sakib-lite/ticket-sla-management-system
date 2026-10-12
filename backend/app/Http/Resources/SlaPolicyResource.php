<?php

namespace App\Http\Resources;

use App\Models\SlaPolicy;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SlaPolicy */
class SlaPolicyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'priority' => $this->priority->value,
            'label' => $this->priority->label(),
            'resolution_hours' => $this->resolution_hours,
            'approaching_threshold_percent' => $this->approaching_threshold_percent,
            'updated_at' => $this->updated_at,
        ];
    }
}
