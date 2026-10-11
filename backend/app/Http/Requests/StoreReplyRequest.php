<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesAttachments;
use App\Models\TicketReply;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

class StoreReplyRequest extends FormRequest
{
    use ValidatesAttachments;

    public function authorize(): bool
    {
        return $this->user()->can('create', [TicketReply::class, $this->route('ticket')]);
    }

    public function rules(): array
    {
        $ticket = $this->route('ticket');

        return [
            'body' => ['required', 'string', 'max:10000'],
            'is_internal' => [
                Rule::prohibitedIf($this->user()->cannot('createInternal', [TicketReply::class, $ticket])),
                'sometimes',
                'boolean',
            ],
            ...$this->attachmentRules(),
        ];
    }

    public function isInternal(): bool
    {
        return (bool) $this->validated('is_internal', false);
    }

    /** @return list<UploadedFile> */
    public function files(): array
    {
        return $this->uploadedFiles();
    }
}
