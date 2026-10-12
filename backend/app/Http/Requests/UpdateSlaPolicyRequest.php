<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSlaPolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('sla_policy'));
    }

    public function rules(): array
    {
        return [
            'resolution_hours' => ['required', 'integer', 'min:1', 'max:720'],
            'approaching_threshold_percent' => ['required', 'integer', 'min:1', 'max:99'],
        ];
    }
}
