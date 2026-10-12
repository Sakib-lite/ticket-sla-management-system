<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('category'));
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:60', Rule::unique('categories', 'name')->ignore($this->route('category'))],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
