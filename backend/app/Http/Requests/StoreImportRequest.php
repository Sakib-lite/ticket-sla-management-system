<?php

namespace App\Http\Requests;

use App\Models\Import;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rules\File;

class StoreImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Import::class);
    }

    public function rules(): array
    {
        return [
            'file' => ['required', File::types(['csv', 'txt'])->max(config('imports.max_kilobytes'))],
        ];
    }

    public function csv(): UploadedFile
    {
        return $this->file('file');
    }
}
