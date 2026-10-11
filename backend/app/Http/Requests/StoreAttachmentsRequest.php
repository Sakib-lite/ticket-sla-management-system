<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesAttachments;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

class StoreAttachmentsRequest extends FormRequest
{
    use ValidatesAttachments;

    public function authorize(): bool
    {
        return $this->user()->can('attach', $this->route('ticket'));
    }

    public function rules(): array
    {
        return $this->attachmentRules(required: true);
    }

    /** @return list<UploadedFile> */
    public function files(): array
    {
        return $this->uploadedFiles();
    }
}
