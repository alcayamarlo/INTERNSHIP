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
        $status = $this->input('status');
        $application = $this->route('application');
        $allowedStatuses = $application?->status->allowedNextStatuses() ?? [];

        $interviewRules = ['nullable'];
        if ($status instanceof ApplicationStatus) {
            $status = $status->value;
        }

        if ($status === ApplicationStatus::Interview->value) {
            $interviewRules = ['required', 'date', 'after:now'];
        } elseif ($status !== null) {
            $interviewRules = ['nullable', 'date', 'after:now'];
        }

        return [
            'status' => [
                'required',
                Rule::enum(ApplicationStatus::class),
                Rule::in(array_map(fn (ApplicationStatus $allowedStatus) => $allowedStatus->value, $allowedStatuses)),
            ],
            'employer_notes' => ['nullable', 'string', 'max:2000'],
            'interview_at' => $interviewRules,
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Please select an application status.',
            'status.in' => 'That status transition is not allowed from the current application status.',
            'interview_at.required' => 'Interview date is required when moving to the interview stage.',
            'interview_at.after' => 'Interview date must be in the future.',
        ];
    }
}
