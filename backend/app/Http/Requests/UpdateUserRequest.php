<?php

namespace App\Http\Requests;

use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('user'));
    }

    public function rules(): array
    {
        $isSelf = $this->user()->is($this->route('user'));

        return [
            'name' => ['sometimes', 'string', 'max:100'],
            'role' => [Rule::prohibitedIf($isSelf), 'sometimes', Rule::enum(Role::class)],
            'is_available' => ['sometimes', 'boolean'],
        ];
    }
}
