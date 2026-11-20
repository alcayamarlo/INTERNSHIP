<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AcademicProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Coordinators manage Academic Settings too.
        return auth()->user()?->isRole(UserRole::Admin, UserRole::Coordinator) ?? false;
    }

    protected function prepareForValidation(): void
    {
        // The coordinator dropdown sends "" for "No coordinator".
        if ($this->has('coordinators')) {
            $this->merge(['coordinators' => array_values(array_filter((array) $this->input('coordinators'), fn ($v) => $v !== '' && $v !== null))]);
        }
    }

    public function rules(): array
    {
        $program = $this->route('program');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('programs', 'name')->ignore($program?->id)],
            'code' => ['required', 'string', 'max:20', 'alpha_num', Rule::unique('programs', 'code')->ignore($program?->id)],
            'required_internship_hours' => ['required', 'integer', 'min:1', 'max:10000'],
            'required_hours_confirmed' => ['nullable', 'boolean'],
            'majors' => ['nullable', 'string', 'max:5000'],
            // Admin only: the department's coordinators. At most \App\Models\Coordinator::MAX_PER_DEPARTMENT, and only users with
            // the coordinator role. (A coordinator editing their own program does not send, and is not allowed to change, this.)
            'coordinators' => [Rule::excludeIf(! (auth()->user()?->isRole(UserRole::Admin) ?? false)), 'nullable', 'array', 'max:'.\App\Models\Coordinator::MAX_PER_DEPARTMENT],
            'coordinators.*' => ['integer', 'distinct', Rule::exists('users', 'id')->where('role', UserRole::Coordinator->value)->where('is_active', true)->whereNull('archived_at')->whereNull('anonymised_at')],
        ];
    }

    public function messages(): array
    {
        return ["coordinators.max" => "A program has one coordinator. Untick the others."];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $names = collect(preg_split('/\r\n|\r|\n/', (string) $this->input('majors')))
                ->map(fn (string $name) => trim($name))
                ->filter();

            if ($names->count() !== $names->unique(fn (string $name) => mb_strtolower($name))->count()) {
                $validator->errors()->add('majors', 'Each major can only be listed once.');
            }
        });
    }
}
