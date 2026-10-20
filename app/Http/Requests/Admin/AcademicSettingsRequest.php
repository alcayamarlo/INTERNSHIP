<?php

namespace App\Http\Requests\Admin;

use App\Support\StudentIdFormat;
use App\Enums\UserRole;
use App\Models\AcademicSetting;
use App\Services\AcademicCalendar;
use Illuminate\Foundation\Http\FormRequest;

class AcademicSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->isRole(UserRole::Admin) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $pattern = $this->input('student_id_pattern');
        if (is_string($pattern)) {
            $this->merge(['student_id_pattern' => trim($pattern)]);
        }
    }

    public function rules(): array
    {
        return [
            'student_id_pattern' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! StudentIdFormat::isValidPattern((string) $value)) {
                        $fail('Enter a valid regular-expression pattern without delimiters.');
                    }
                },
            ],
            'require_fourth_year_standing' => ['nullable', 'boolean'],
            'require_major_subjects_passed' => ['nullable', 'boolean'],
            'academic_year_start_month' => ['required', 'integer', 'between:1,12'],
            'summer_enabled' => ['nullable', 'boolean'],
            'current_term_override' => ['nullable', 'in:first,second,summer'],
            'first_semester_start_month' => ['required', 'integer', 'between:1,12'],
            'first_semester_start_day' => ['required', 'integer', 'between:1,31'],
            'first_semester_end_month' => ['required', 'integer', 'between:1,12'],
            'first_semester_end_day' => ['required', 'integer', 'between:1,31'],
            'second_semester_start_month' => ['required', 'integer', 'between:1,12'],
            'second_semester_start_day' => ['required', 'integer', 'between:1,31'],
            'second_semester_end_month' => ['required', 'integer', 'between:1,12'],
            'second_semester_end_day' => ['required', 'integer', 'between:1,31'],
            'summer_start_month' => ['required', 'integer', 'between:1,12'],
            'summer_start_day' => ['required', 'integer', 'between:1,31'],
            'summer_end_month' => ['required', 'integer', 'between:1,12'],
            'summer_end_day' => ['required', 'integer', 'between:1,31'],
            'required_ojt_hours' => ['sometimes', 'required', 'integer', 'between:1,2000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $ranges = [];
            foreach (['first_semester' => 'first', 'second_semester' => 'second', 'summer' => 'summer'] as $prefix => $key) {
                $ranges[$key] = [
                    'start_month' => (int) $this->input($prefix.'_start_month'),
                    'start_day' => (int) $this->input($prefix.'_start_day'),
                    'end_month' => (int) $this->input($prefix.'_end_month'),
                    'end_day' => (int) $this->input($prefix.'_end_day'),
                ];
            }

            if (! app(AcademicCalendar::class)->rangesAreContinuous($ranges)) {
                $validator->errors()->add('term_ranges', 'Term date ranges must be valid, continuous, and have no overlaps or gaps.');
            }

            if ((int) $this->input('first_semester_start_month') !== (int) $this->input('academic_year_start_month')) {
                $validator->errors()->add('academic_year_start_month', 'The academic year start month must match the 1st semester start month. Update both settings together.');
            }

            if ($this->input('current_term_override') === 'summer' && ! $this->boolean('summer_enabled')) {
                $validator->errors()->add('current_term_override', 'Enable Summer before using it as the current term override.');
            }
        });
    }
}
