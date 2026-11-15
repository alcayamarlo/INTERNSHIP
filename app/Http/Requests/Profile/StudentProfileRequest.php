<?php

namespace App\Http\Requests\Profile;

use App\Enums\UserRole;
use App\Models\AcademicSetting;
use App\Models\Program;
use App\Services\AcademicCalendar;
use App\Services\ImageProcessor;
use App\Services\OjtDocumentData;
use App\Support\PrivacyConsent;
use App\Support\StudentIdFormat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StudentProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()?->user()?->isRole(UserRole::Student) ?? false;
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
        // Names: trimmed, single spaces, Title Case ("joshua  d.  opsima" -> "Joshua D. Opsima"; II/III kept).
        foreach (['first_name', 'middle_name', 'last_name'] as $nameField) {
            if (is_string($this->input($nameField))) {
                $normalized[$nameField] = OjtDocumentData::normalizeName($this->input($nameField));
            }
        }
        if ($this->has('suffix')) {
            $normalized['suffix'] = OjtDocumentData::normalizeSuffix(is_string($this->input('suffix')) ? $this->input('suffix') : null);
        }
        foreach (['phone', 'emergency_contact_phone'] as $phoneField) {
            if ($this->filled($phoneField)) {
                $normalized[$phoneField] = preg_replace('/[\s().-]+/', '', $this->input($phoneField));
            }
        }
        if ($this->filled('expected_graduation') && preg_match('/^\d{4}-\d{2}$/', (string) $this->input('expected_graduation'))) {
            $normalized['expected_graduation'] = $this->input('expected_graduation').'-01';
        }
        $this->merge($normalized);
    }

    public const SECTIONS = ['personal', 'contact', 'academic'];

    public const CIVIL_STATUSES = ['Single', 'Married', 'Widowed', 'Separated'];

    private const PHONE_REGEX = 'regex:/^(\+?\d{1,3}[-.\s]?)?\d{7,14}$/';

    /** Philippine mobile number: 09XXXXXXXXX or +639XXXXXXXXX (spaces, dashes and brackets are removed first). */
    private const PH_MOBILE_REGEX = 'regex:/^(09\d{9}|\+639\d{9})$/';

    /**
     * The profile sections this request saves: one tab, or all three when no section is given.
     *
     * @return list<string>
     */
    public function sections(): array
    {
        $section = $this->input('profile_section');

        return in_array($section, self::SECTIONS, true) ? [$section] : self::SECTIONS;
    }

    public function rules(): array
    {
        $rules = ['profile_section' => ['nullable', Rule::in(self::SECTIONS)]];
        foreach ($this->sections() as $section) {
            $rules += match ($section) {
                'personal' => $this->personalRules(),
                'contact' => $this->contactRules(),
                'academic' => $this->academicRules(),
            };
        }

        return $rules;
    }

    private function personalRules(): array
    {
        return [
            'first_name' => ['required', 'string', 'min:2', 'max:50', 'regex:'.OjtDocumentData::NAME_REGEX],
            'middle_name' => ['nullable', 'string', 'max:50', 'regex:'.OjtDocumentData::NAME_REGEX],
            'last_name' => ['required', 'string', 'min:2', 'max:50', 'regex:'.OjtDocumentData::NAME_REGEX],
            'suffix' => ['nullable', Rule::in(OjtDocumentData::SUFFIXES)],
            'gender' => ['required', 'string', 'in:Male,Female,Other,Prefer Not to Say'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'place_of_birth' => ['nullable', 'string', 'max:150'],
            'citizenship' => ['nullable', 'string', 'max:100'],
            'languages' => ['nullable', 'string', 'max:255'],
            'civil_status' => ['nullable', Rule::in(self::CIVIL_STATUSES)],
            'religion' => ['nullable', 'string', 'max:100'],
            'height_cm' => ['nullable', 'numeric', 'between:50,250'],
            'weight_kg' => ['nullable', 'numeric', 'between:20,300'],
            'show_civil_status' => ['nullable', 'boolean'],
            'show_religion' => ['nullable', 'boolean'],
            'show_height' => ['nullable', 'boolean'],
            'show_weight' => ['nullable', 'boolean'],
            'profile_picture' => ImageProcessor::rules(required: false),
        ];
    }

    private function contactRules(): array
    {
        $socialUrl = ['nullable', 'url:http,https', 'max:255'];

        return [
            'email' => ['required', 'email', 'unique:users,email,'.(auth()?->id() ?? 'NULL')],
            'phone' => ['required', self::PH_MOBILE_REGEX],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'province' => ['required', 'string', 'max:100'],
            'zip_code' => ['required', 'string', 'max:20'],
            'provincial_address' => ['nullable', 'string', 'max:255'],
            'linkedin_url' => $socialUrl,
            'facebook_url' => $socialUrl,
            'github_url' => $socialUrl,
            'emergency_contact_name' => ['required', 'string', 'max:150'],
            'emergency_contact_relationship' => ['required', 'string', 'max:60'],
            'emergency_contact_address' => ['required', 'string', 'max:255'],
            'emergency_contact_phone' => ['required', self::PHONE_REGEX],
        ];
    }

    private function academicRules(): array
    {
        $student = $this->user()?->student;
        $studentNumber = $this->input('student_number');
        $studentNumberChanged = $studentNumber !== $student?->student_id_number;
        $graduationYear = ['required', 'integer', 'between:1950,'.now()->year];

        return [
            'student_number' => [
                'nullable',
                'string',
                'max:50',
                ...($studentNumberChanged
                    ? [Rule::unique('students', 'student_id_number')->ignore($student?->id)]
                    : []),
            ],
            'program_id' => ['required_without:program', 'nullable', 'integer', 'exists:programs,id'],
            'major_id' => ['nullable', 'integer', 'exists:majors,id'],
            'program' => ['nullable', 'string', 'max:255'],
            'department' => ['required', 'string', 'max:100'],
            'year_level' => ['required', 'string', 'in:1st Year,2nd Year,3rd Year,4th Year'],
            'section' => ['nullable', 'string', 'max:50'],
            'school_year' => ['nullable', 'string', Rule::in(app(AcademicCalendar::class)->schoolYears())],
            'semester' => ['nullable', Rule::in(AcademicSetting::current()->summer_enabled
                ? ['first', 'second', 'summer']
                : ['first', 'second'])],
            'enrollment_status' => ['nullable', 'string', 'in:Regular,Irregular'],
            'gwa' => ['nullable', 'numeric', 'between:1,5', 'decimal:0,2'],
            'expected_graduation' => ['required', 'date_format:Y-m-d'],
            'elementary_school' => ['required', 'string', 'max:255'],
            'elementary_year_graduated' => $graduationYear,
            'high_school' => ['required', 'string', 'max:255'],
            'high_school_year_graduated' => $graduationYear,

            // Career Information
            'career_objectives' => ['nullable', 'string', 'max:1000'],
            'preferred_internship_field' => ['nullable', 'string', 'max:255'],
            'preferred_work_setup' => ['nullable', 'string', 'in:Remote,Hybrid,Onsite'],
            'preferred_location' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function after(): array
    {
        return [
            function ($validator): void {
                $student = $this->user()?->student;
                if ($student && PrivacyConsent::isMissing($this, $student)) {
                    $validator->errors()->add('privacy_consent', PrivacyConsent::REQUIRED_MESSAGE);
                }

                if (! in_array('academic', $this->sections(), true)) {
                    return;
                }

                $elementaryYear = $this->integer('elementary_year_graduated');
                $highSchoolYear = $this->integer('high_school_year_graduated');
                if ($elementaryYear && $highSchoolYear && $highSchoolYear < $elementaryYear) {
                    $validator->errors()->add('high_school_year_graduated', 'High school graduation must be after elementary graduation.');
                }

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
                } elseif ($student?->student_id_locked
                    && (string) ($studentNumber ?? '') !== (string) ($student->student_id_number ?? '')) {
                    $validator->errors()->add(
                        'student_number',
                        "Your student ID was set when your account was created and can't be changed here. Contact your coordinator or administrator if it needs correction."
                    );
                } elseif (filled($student?->student_id_number)
                    && ! $student->studentIdNeedsCorrection()
                    && (string) ($studentNumber ?? '') !== (string) $student->student_id_number) {
                    // Saved once in the right format: only a coordinator or admin can change it.
                    $validator->errors()->add(
                        'student_number',
                        "Your student ID can't be changed after it is saved. Contact your coordinator or administrator if it needs correction."
                    );
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
            'first_name.regex' => 'Use letters, spaces, hyphens and periods only.',
            'middle_name.regex' => 'Use letters, spaces, hyphens and periods only.',
            'last_name.regex' => 'Use letters, spaces, hyphens and periods only.',
            'first_name.min' => 'First name must be at least 2 characters.',
            'last_name.min' => 'Last name must be at least 2 characters.',
            'first_name.max' => 'First name may not be longer than 50 characters.',
            'middle_name.max' => 'Middle name may not be longer than 50 characters.',
            'last_name.max' => 'Last name may not be longer than 50 characters.',
            'suffix.in' => 'Choose a suffix: Jr., Sr., II, III, IV or V.',
            'email.unique' => 'This email is already in use.',
            'phone.regex' => 'Enter a Philippine mobile number: 09XXXXXXXXX or +639XXXXXXXXX.',
            'date_of_birth.before' => 'Date of birth must be in the past.',
            'gender.required' => 'Gender is required.',
            'date_of_birth.required' => 'Date of birth is required.',
            'department.required' => 'College is required.',
            'program_id.required_without' => 'Course is required.',
            'year_level.required' => 'Year level is required.',
            'expected_graduation.required' => 'Expected graduation is required.',
            'emergency_contact_phone.regex' => 'Please enter a valid phone number.',
            'elementary_year_graduated.between' => 'Enter a year between 1950 and this year.',
            'high_school_year_graduated.between' => 'Enter a year between 1950 and this year.',
            'linkedin_url.url' => 'Enter a full link starting with https://.',
            'facebook_url.url' => 'Enter a full link starting with https://.',
            'github_url.url' => 'Enter a full link starting with https://.',
            ...ImageProcessor::messages('profile_picture'),
        ];
    }
}
