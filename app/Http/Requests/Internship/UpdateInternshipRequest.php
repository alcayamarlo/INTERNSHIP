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
}
