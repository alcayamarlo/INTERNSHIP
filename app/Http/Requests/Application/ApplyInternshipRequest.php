<?php

namespace App\Http\Requests\Application;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApplyInternshipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isRole(UserRole::Student) ?? false;
    }

    public function rules(): array
    {
        $resumeRequirement = $this->routeIs('student.internships.apply')
            ? ['required', 'integer']
            : ['sometimes', 'integer'];

        return [
            'resume_id' => [
                ...$resumeRequirement,
                Rule::exists('resumes', 'id')
                    ->where('student_id', $this->user()?->student?->id ?? 0)
                    ->whereNull('archived_at'),
            ],
            'cover_letter' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'resume_id.required' => 'Choose an active resume to apply.',
            'resume_id.exists' => 'Choose one of your non-archived resumes.',
        ];
    }
}
