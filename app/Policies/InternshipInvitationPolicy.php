<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Internship;
use App\Models\InternshipInvitation;
use App\Models\User;

class InternshipInvitationPolicy
{
    /** Only the employer who owns the internship can invite for it. */
    public function create(User $user, Internship $internship): bool
    {
        return $user->isRole(UserRole::Employer)
            && $user->employer !== null
            && (int) $internship->employer_id === (int) $user->employer->id;
    }

    public function view(User $user, InternshipInvitation $invitation): bool
    {
        return $this->ownsAsEmployer($user, $invitation) || $this->isInvitedStudent($user, $invitation);
    }

    /** Accept or decline: only the invited student. */
    public function respond(User $user, InternshipInvitation $invitation): bool
    {
        return $this->isInvitedStudent($user, $invitation);
    }

    private function ownsAsEmployer(User $user, InternshipInvitation $invitation): bool
    {
        return $user->isRole(UserRole::Employer)
            && $user->employer !== null
            && (int) $invitation->employer_id === (int) $user->employer->id;
    }

    private function isInvitedStudent(User $user, InternshipInvitation $invitation): bool
    {
        return $user->isRole(UserRole::Student)
            && $user->student !== null
            && (int) $invitation->student_id === (int) $user->student->id;
    }
}
