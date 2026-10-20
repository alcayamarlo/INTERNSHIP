<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Placement;
use App\Models\User;

class PlacementPolicy
{
    public function view(User $user, Placement $placement): bool
    {
        return match ($user->role) {
            UserRole::Admin => true,
            UserRole::Student => $this->isStudentOwner($user, $placement),
            UserRole::Employer => $this->isEmployerOwner($user, $placement),
            UserRole::Coordinator => (bool) $user->coordinator?->canAccessStudent($placement->student),
            default => false,
        };
    }

    /** Student logs time and writes journals on an ongoing placement. */
    public function log(User $user, Placement $placement): bool
    {
        return $user->isRole(UserRole::Student)
            && $this->isStudentOwner($user, $placement)
            && $placement->isActive();
    }

    /** Employer approves hours, comments on journals and evaluates. */
    public function supervise(User $user, Placement $placement): bool
    {
        return $user->isRole(UserRole::Employer) && $this->isEmployerOwner($user, $placement);
    }

    public function clear(User $user, Placement $placement): bool
    {
        return $user->isRole(UserRole::Coordinator)
            && $placement->isCompleted()
            && ! $placement->isCleared()
            && (bool) $user->coordinator?->canAccessStudent($placement->student);
    }

    public function downloadCertificate(User $user, Placement $placement): bool
    {
        return $placement->isCleared() && $this->view($user, $placement);
    }

    private function isStudentOwner(User $user, Placement $placement): bool
    {
        return $user->student !== null && (int) $placement->student_id === (int) $user->student->id;
    }

    private function isEmployerOwner(User $user, Placement $placement): bool
    {
        return $user->employer !== null && (int) $placement->employer_id === (int) $user->employer->id;
    }
}
