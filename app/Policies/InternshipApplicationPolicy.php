<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\InternshipApplication;
use App\Models\User;

class InternshipApplicationPolicy
{
    public function view(User $user, InternshipApplication $application): bool
    {
        if ($user->isRole(UserRole::Employer)) {
            return $user->employer?->id === $application->internship?->employer_id;
        }

        if ($user->isRole(UserRole::Student)) {
            return $user->student?->id === $application->student_id;
        }

        return false;
    }

    public function updateStatus(User $user, InternshipApplication $application): bool
    {
        return $user->isRole(UserRole::Employer)
            && $user->employer?->id === $application->internship?->employer_id;
    }

    public function downloadResume(User $user, InternshipApplication $application): bool
    {
        return $this->updateStatus($user, $application);
    }
}
