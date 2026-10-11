<?php

namespace App\Http\Resources;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Ticket */
class TicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ticket_number' => $this->ticket_number,
            'subject' => $this->subject,
            'description' => $this->description,
            'priority' => $this->priority->value,
            'status' => $this->status->value,
            'category' => CategoryResource::make($this->whenLoaded('category')),
            'creator' => UserResource::make($this->whenLoaded('creator')),
            'assignee' => UserResource::make($this->whenLoaded('assignee')),
            'sla' => [
                'status' => $this->liveSlaStatus()->value,
                'due_at' => $this->sla_due_at,
                'approaching_at' => $this->sla_approaching_at,
                'escalated_at' => $this->escalated_at,
                'resolved_within_sla' => $this->resolved_within_sla,
            ],
            'resolved_at' => $this->resolved_at,
            'closed_at' => $this->closed_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
