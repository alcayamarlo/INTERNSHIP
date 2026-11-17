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
            // The HR account can always look at its company's interns; acting is limited by supervise().
            UserRole::Employer => $this->isEmployerOwner($user, $placement),
            UserRole::Supervisor => $placement->supervisor_user_id !== null && (int) $placement->supervisor_user_id === (int) $user->id,
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

    /**
     * Approves hours, certifies reports and evaluates. With an assigned (active) supervisor only that
     * supervisor may; with none, the company's HR account does it, so current OJTs never stop.
     */
    public function supervise(User $user, Placement $placement): bool
    {
        $assigned = $placement->activeSupervisor();

        return match ($user->role) {
            UserRole::Supervisor => $assigned !== null && $assigned->is($user),
            UserRole::Employer => $assigned === null && $this->isEmployerOwner($user, $placement),
            default => false,
        };
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

    /** Coordinators managing the student request Monthly Evaluations (active placements are checked in AppraisalService). */
    public function requestAppraisal(User $user, Placement $placement): bool
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
