<?php

namespace App\Http\Requests;

use App\Enums\TicketPriority;
use App\Http\Requests\Concerns\ValidatesAttachments;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

class StoreTicketRequest extends FormRequest
{
    use ValidatesAttachments;

    public function authorize(): bool
    {
        return $this->user()->can('create', Ticket::class);
    }

    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:10000'],
            'priority' => ['required', Rule::enum(TicketPriority::class)],
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')->where('is_active', true)],
            'customer_id' => [
                Rule::prohibitedIf($this->user()->isCustomer()),
                'sometimes',
                'integer',
                Rule::exists('users', 'id')->where('role', 'customer'),
            ],
            ...$this->attachmentRules(),
        ];
    }

    public function customer(): User
    {
        $customerId = $this->validated('customer_id');

        return $customerId ? User::findOrFail($customerId) : $this->user();
    }

    /** @return array{subject: string, description: string, priority: string, category_id: int} */
    public function ticketAttributes(): array
    {
        return $this->safe()->only(['subject', 'description', 'priority', 'category_id']);
    }

    /** @return list<UploadedFile> */
    public function files(): array
    {
        return $this->uploadedFiles();
    }
}
