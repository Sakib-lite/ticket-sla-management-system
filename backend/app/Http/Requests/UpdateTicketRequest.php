<?php

namespace App\Http\Requests;

use App\Enums\TicketPriority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('ticket'));
    }

    public function rules(): array
    {
        $ticket = $this->route('ticket');
        $staffOnly = Rule::prohibitedIf($this->user()->cannot('changePriority', $ticket));

        return [
            'subject' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string', 'max:10000'],
            'priority' => [$staffOnly, 'sometimes', Rule::enum(TicketPriority::class)],
            'category_id' => [$staffOnly, 'sometimes', 'integer', Rule::exists('categories', 'id')->where('is_active', true)],
        ];
    }
}
