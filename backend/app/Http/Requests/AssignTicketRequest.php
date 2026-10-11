<?php

namespace App\Http\Requests;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('assign', $this->route('ticket'));
    }

    public function rules(): array
    {
        return [
            'agent_id' => ['required', 'integer', Rule::exists('users', 'id')->where('role', Role::Agent->value)],
        ];
    }

    public function agent(): User
    {
        return User::findOrFail($this->validated('agent_id'));
    }
}
