<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use App\Support\StudentIdFormat;
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
            'role' => ['required', Rule::in(array_map(
                fn (UserRole $role) => $role->value,
                UserRole::assignable()
            ))],
            'phone' => ['nullable', 'string', 'max:20'],
            'institution_id' => ['required_if:role,coordinator,student', 'nullable', 'exists:institutions,id'],
            'company_name' => ['required_if:role,employer', 'nullable', 'string', 'max:255'],
            'student_id_number' => [
                'required_if:role,student',
                'nullable',
                'string',
                'max:50',
                'unique:students,student_id_number',
                function ($attribute, $value, $fail) {
                    if ($value !== null && ! StudentIdFormat::matches($value)) {
                        $fail('Enter a student ID in the format SCC-21-00013240.');
                    }
                },
            ],
        ];
    }
}