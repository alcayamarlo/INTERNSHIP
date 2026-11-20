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
            'company_name' => ['required_if:role,employer', 'nullable', 'string', 'max:255'],
            // Coordinators only: one department each, and one coordinator per program.
            'program_id' => [
                'exclude_unless:role,coordinator',
                'required',
                'integer',
                'exists:programs,id',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (! \App\Models\Coordinator::departmentHasRoom((int) $value)) {
                        $fail(\App\Models\Coordinator::DEPARTMENT_TAKEN);
                    }
                },
            ],
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

    public function messages(): array
    {
        return [
            'program_id.required' => 'Choose the department this coordinator will handle.',
        ];
    }
}