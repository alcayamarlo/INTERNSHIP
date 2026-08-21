<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isRole(UserRole::Admin) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => ['required', Rule::in([
                UserRole::Employer->value,
                UserRole::Coordinator->value,
            ])],
            'phone' => ['nullable', 'string', 'max:20'],
            'institution_id' => ['required_if:role,coordinator', 'nullable', 'exists:institutions,id'],
            'company_name' => ['required_if:role,employer', 'nullable', 'string', 'max:255'],
        ];
    }
}