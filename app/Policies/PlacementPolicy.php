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
            UserRole::Coordinator => $this->coordinatorCanAccess($user, $placement),
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
            && $this->coordinatorCanAccess($user, $placement);
    }

    public function requestRating(User $user, Placement $placement): bool
    {
        return $user->isRole(UserRole::Coordinator) && $this->coordinatorCanAccess($user, $placement);
    }

    public function downloadCertificate(User $user, Placement $placement): bool
    {
        return $placement->isCleared() && $this->view($user, $placement);
    }

    private function coordinatorCanAccess(User $user, Placement $placement): bool
    {
        // The student may be soft-deleted; without it there is nothing to scope against.
        return $user->coordinator !== null
            && $placement->student !== null
            && $user->coordinator->canAccessStudent($placement->student);
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
