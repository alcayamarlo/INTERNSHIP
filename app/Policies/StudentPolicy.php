<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Student;
use App\Models\User;
use App\Services\CompanyVisibleProfile;

class StudentPolicy
{
    public function view(User $user, Student $student): bool
    {
        if ($user->isRole(UserRole::Admin)) {
            return true;
        }

        if ($user->isRole(UserRole::Student)) {
            return $user->student?->id === $student->id;
        }

        if ($user->isRole(UserRole::Coordinator) && $user->coordinator) {
            return $user->coordinator->canAccessStudent($student);
        }

        return false;
    }

    /**
     * The 2x2 picture: the student, their assigned coordinator, and companies the student applied to
     * (after giving privacy consent). Nobody else, admins included.
     */
    public function viewIdPhoto(User $user, Student $student): bool
    {
        return match (true) {
            $user->isRole(UserRole::Student) => (int) $user->student?->id === (int) $student->id,
            $user->isRole(UserRole::Coordinator) => $user->coordinator?->canAccessStudent($student) === true,
            $user->isRole(UserRole::Employer) => $student->hasPrivacyConsent()
                && app(CompanyVisibleProfile::class)->hasApplied($student, $user),
            default => false,
        };
    }
}
