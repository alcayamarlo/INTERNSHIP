<?php

namespace App\Http\Requests\Auth;

use App\Enums\UserRole;
use App\Models\Program;
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
                Password::min(8),
                'regex:/[A-Z]/',
            ],
            'role' => ['required', Rule::in([UserRole::Student->value])],
            'phone' => ['nullable', 'string', 'max:20'],
            'institution_id' => ['required', 'exists:institutions,id'],
            'company_name' => ['nullable'],
            'program_id' => ['required_without:program', 'nullable', 'exists:programs,id'],
            'program' => ['required_without:program_id', 'nullable', 'string', 'max:255'],
        ];
    }

    public function after(): array
    {
        return [
            function ($validator): void {
                if ($this->filled('program') && ! $this->filled('program_id')
                    && ! Program::query()->where('name', $this->input('program'))->orWhere('code', $this->input('program'))->exists()
                    && ! in_array($this->input('program'), [
                        'Bachelor of Science in Information Technology',
                        'Bachelor of Science in Hospitality Management',
                        'Bachelor of Science in Business Administration',
                    ], true)) {
                    $validator->errors()->add('program', 'Choose one of the programs offered by the school.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Full name is required.',
            'email.required' => 'Email address is required.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email is already registered. Sign in, use "Forgot password", or contact the administrator if your account was archived.',
            'password.required' => 'Password is required.',
            'password.confirmed' => 'Passwords do not match.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.regex' => 'Password must contain at least one uppercase letter.',
            'role.required' => 'Please select a role.',
            'company_name.required_if' => 'Company name is required for employers.',
        ];
    }
}
