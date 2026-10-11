<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rules\File;

trait ValidatesAttachments
{
    /** @return array<string, list<mixed>> */
    protected function attachmentRules(bool $required = false): array
    {
        return [
            'files' => [$required ? 'required' : 'sometimes', 'array', 'max:'.config('attachments.max_files')],
            'files.*' => [
                File::types(config('attachments.allowed_types'))->max(config('attachments.max_kilobytes')),
            ],
        ];
    }

    /** @return list<UploadedFile> */
    protected function uploadedFiles(): array
    {
        return array_values($this->validated('files', []));
    }
}
