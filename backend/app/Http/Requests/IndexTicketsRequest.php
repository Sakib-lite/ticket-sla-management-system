<?php

namespace App\Http\Requests;

use App\Enums\SlaStatus;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Http\Filters\TicketFilters;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexTicketsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'view' => ['sometimes', Rule::in(['active', 'resolved', 'all'])],
            'status' => ['sometimes', Rule::enum(TicketStatus::class)],
            'priority' => ['sometimes', Rule::enum(TicketPriority::class)],
            'sla_status' => ['sometimes', Rule::enum(SlaStatus::class)],
            'category_id' => ['sometimes', 'integer', 'exists:categories,id'],
            'created_by' => ['sometimes', 'integer', 'exists:users,id'],
            'assignee' => ['sometimes', 'regex:/^(unassigned|\d+)$/'],
            'created_from' => ['sometimes', 'date_format:Y-m-d'],
            'created_to' => ['sometimes', 'date_format:Y-m-d', 'after_or_equal:created_from'],
            'search' => ['sometimes', 'string', 'max:100'],
            'sort' => ['sometimes', Rule::in(TicketFilters::SORTABLE)],
            'direction' => ['sometimes', Rule::in(['asc', 'desc'])],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    public function filters(): TicketFilters
    {
        return new TicketFilters($this->safe()->except(['per_page', 'page']));
    }

    public function perPage(): int
    {
        return (int) $this->validated('per_page', 20);
    }
}
