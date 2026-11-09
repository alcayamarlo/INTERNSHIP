<?php

namespace App\Http\Requests\Internship;

use App\Enums\UserRole;

class UpdateInternshipRequest extends StoreInternshipRequest
{
    public function authorize(): bool
    {
        $internship = $this->route('internship');

        return $this->user()?->isRole(UserRole::Employer)
            && $internship
            && $internship->employer_id === $this->user()->employer?->id;
    }

    /** An expired posting can still be edited without changing its (past) deadline. */
    protected function deadlineRules(): array
    {
        $current = $this->route('internship')?->expires_at?->format('Y-m-d');

        if ($current !== null && $this->input('expires_at') === $current) {
            return ['nullable', 'date_format:Y-m-d'];
        }

        return parent::deadlineRules();
    }
}
