<?php

namespace App\Http\Requests\Application;

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateApplicationStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $application = $this->route('application');

        return $this->user()?->isRole(UserRole::Employer)
            && $application
            && $application->internship?->employer_id === $this->user()->employer?->id;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(ApplicationStatus::class)],
            'employer_notes' => ['nullable', 'string', 'max:2000'],
            'interview_at' => ['nullable', 'date', 'after:now'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Please select an application status.',
            'interview_at.after' => 'Interview date must be in the future.',
        ];
    }
}
