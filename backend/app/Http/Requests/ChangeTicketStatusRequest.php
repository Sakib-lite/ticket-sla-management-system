<?php

namespace App\Http\Requests;

use App\Enums\TicketStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeTicketStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('changeStatus', $this->route('ticket'));
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(TicketStatus::class)],
        ];
    }

    public function status(): TicketStatus
    {
        return TicketStatus::from($this->validated('status'));
    }
}
