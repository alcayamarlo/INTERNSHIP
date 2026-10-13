<?php

namespace App\Http\Requests\Profile;

use App\Models\AcademicSetting;
use App\Models\Program;
use App\Services\FileUploadService;
use App\Services\AcademicCalendar;
use App\Support\StudentIdFormat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StudentProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()?->user()?->isRole(\App\Enums\UserRole::Student) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $normalized = [];
        $studentNumber = $this->input('student_number');
        if (is_string($studentNumber)) {
            $studentNumber = trim($studentNumber);
            $normalized['student_number'] = $studentNumber === '' ? null : mb_strtoupper($studentNumber);
        }
        $gwa = $this->input('gwa');
        if (is_string($gwa) && trim($gwa) === '') {
            $normalized['gwa'] = null;
        }
        if ($this->filled('phone')) {
            $normalized['phone'] = preg_replace('/[\s().-]+/', '', $this->input('phone'));
        }
        if ($this->filled('expected_graduation') && preg_match('/^\d{4}-\d{2}$/', (string) $this->input('expected_graduation'))) {
            $normalized['expected_graduation'] = $this->input('expected_graduation').'-01';
        }
        $this->merge($normalized);
    }

    public function rules(): array
    {
        $student = $this->user()?->student;
        $studentNumber = $this->input('student_number');
        $studentNumberChanged = $studentNumber !== $student?->student_id_number;

        return [
            // Personal Information
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'suffix' => ['nullable', 'string', 'max:20'],
            'gender' => ['nullable', 'string', 'in:Male,Female,Other,Prefer Not to Say'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],

            // Contact Information
            'email' => ['required', 'email', 'unique:users,email,' . (auth()?->id() ?? 'NULL')],
            'phone' => ['required', 'regex:/^(\+?\d{1,3}[-.\s]?)?\d{7,14}$/'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'province' => ['required', 'string', 'max:100'],
            'zip_code' => ['required', 'string', 'max:20'],

            // Academic Information
            'student_number' => [
                'nullable',
                'string',
                'max:50',
                ...($studentNumberChanged
                    ? [Rule::unique('students', 'student_id_number')->ignore($student?->id)]
                    : []),
            ],
            'program_id' => ['nullable', 'integer', 'exists:programs,id'],
            'major_id' => ['nullable', 'integer', 'exists:majors,id'],
            'program' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:100'],
            'year_level' => ['nullable', 'string', 'in:1st Year,2nd Year,3rd Year,4th Year'],
            'section' => ['nullable', 'string', 'max:50'],
            'school_year' => ['nullable', 'string', Rule::in(app(AcademicCalendar::class)->schoolYears())],
            'semester' => ['nullable', Rule::in(AcademicSetting::current()->summer_enabled
                ? ['first', 'second', 'summer']
                : ['first', 'second'])],
            'enrollment_status' => ['nullable', 'string', 'in:Regular,Irregular'],
            'gwa' => ['nullable', 'numeric', 'between:1,5', 'decimal:0,2'],
            'expected_graduation' => ['nullable', 'date_format:Y-m-d'],

            // Career Information
            'career_objectives' => ['nullable', 'string', 'max:1000'],
            'preferred_internship_field' => ['nullable', 'string', 'max:255'],
            'preferred_work_setup' => ['nullable', 'string', 'in:Remote,Hybrid,Onsite'],
            'preferred_location' => ['nullable', 'string', 'max:255'],

            // Profile Picture
            'profile_picture' => FileUploadService::imageRules(),
        ];
    }

    public function after(): array
    {
        return [
            function ($validator): void {
                $programId = $this->integer('program_id');
                $majorId = $this->integer('major_id');

                if ($programId && $majorId && ! Program::query()->whereKey($programId)->whereHas('majors', fn ($query) => $query->whereKey($majorId))->exists()) {
                    $validator->errors()->add('major_id', 'Select a major belonging to the selected program.');
                }

                $student = $this->user()?->student;
                $studentNumber = $this->input('student_number');
                if ($student?->profile_verified_at) {
                    $lockedFields = [
                        'student_number' => $student->student_id_number,
                        'program_id' => $student->program_id,
                        'year_level' => $student->year_level,
                    ];

                    foreach ($lockedFields as $field => $savedValue) {
                        $submittedValue = $this->input($field);
                        $sameValue = $field === 'program_id'
                            ? (string) ($submittedValue ?? '') === (string) ($savedValue ?? '')
                            : (string) ($submittedValue ?? '') === (string) ($savedValue ?? '');

                        if (! $sameValue) {
                            $validator->errors()->add(
                                $field,
                                'This field is locked after coordinator verification. Request a change from your coordinator.'
                            );
                        }
                    }
                }
                if (filled($studentNumber) && $studentNumber !== $student?->student_id_number) {
                    if (! StudentIdFormat::matches($studentNumber)) {
                        $validator->errors()->add('student_number', 'Enter a student ID in the format SCC-21-00013240.');
                    }
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'student_number.unique' => 'This student ID is already in use.',
            'gwa.numeric' => 'Enter a GWA between 1.00 and 5.00.',
            'gwa.between' => 'Enter a GWA between 1.00 and 5.00.',
            'gwa.decimal' => 'Enter a GWA between 1.00 and 5.00.',
            'first_name.required' => 'First name is required.',
            'last_name.required' => 'Last name is required.',
            'email.unique' => 'This email is already in use.',
            'phone.regex' => 'Please enter a valid phone number.',
            'date_of_birth.before' => 'Date of birth must be in the past.',
            'profile_picture.image' => 'The profile picture must be an image.',
            'profile_picture.mimes' => 'Profile picture must be JPG, JPEG, or PNG.',
            'profile_picture.max' => 'Profile picture must not exceed 2MB.',
        ];
    }
}
