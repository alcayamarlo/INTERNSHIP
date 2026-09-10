<?php

namespace App\Http\Requests\Auth;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['role' => UserRole::Student->value]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => [
                'required',
                'confirmed',
                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ],
            'role' => ['required', Rule::in([UserRole::Student->value])],
            'phone' => ['nullable', 'string', 'max:20'],
            'institution_id' => ['required', 'exists:institutions,id'],
            'company_name' => ['nullable'],
            'program' => ['required', Rule::in([
                'Bachelor of Science in Information Technology',
                'Bachelor of Science in Hospitality Management',
                'Bachelor of Science in Business Administration',
            ])],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Full name is required.',
            'email.required' => 'Email address is required.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email is already registered.',
            'password.required' => 'Password is required.',
            'password.confirmed' => 'Passwords do not match.',
            'role.required' => 'Please select a role.',
            'company_name.required_if' => 'Company name is required for employers.',
        ];
    }
}
