<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AcademicProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->isRole(UserRole::Admin) ?? false;
    }

    public function rules(): array
    {
        $program = $this->route('program');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('programs', 'name')->ignore($program?->id)],
            'code' => ['required', 'string', 'max:20', 'alpha_num', Rule::unique('programs', 'code')->ignore($program?->id)],
            'required_internship_hours' => ['required', 'integer', 'min:1', 'max:10000'],
            'required_hours_confirmed' => ['nullable', 'boolean'],
            'coordinator_id' => ['nullable', 'exists:coordinators,id'],
            'majors' => ['nullable', 'string', 'max:5000'],
        ];
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
