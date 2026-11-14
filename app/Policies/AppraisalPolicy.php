<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Appraisal;
use App\Models\User;

/**
 * Who may act on a Monthly Evaluation. The order of steps is enforced in AppraisalService;
 * this decides only whose form it is.
 */
class AppraisalPolicy
{
    public function __construct(private PlacementPolicy $placements) {}

    /** View and print: the student (own), the supervisor (own company), coordinators managing the student, admins. */
    public function view(User $user, Appraisal $appraisal): bool
    {
        return $appraisal->placement !== null && $this->placements->view($user, $appraisal->placement);
    }

    /** The student's rating and the student's comment. */
    public function submitAsStudent(User $user, Appraisal $appraisal): bool
    {
        return $user->isRole(UserRole::Student) && $this->view($user, $appraisal);
    }

    /** The on-site supervisor's rating and comment. */
    public function submitAsSupervisor(User $user, Appraisal $appraisal): bool
    {
        return $appraisal->placement !== null && $this->placements->supervise($user, $appraisal->placement);
    }

    /** The adviser (coordinator) column, and reopening a step. */
    public function submitAsAdviser(User $user, Appraisal $appraisal): bool
    {
        return $appraisal->placement !== null && $this->placements->requestRating($user, $appraisal->placement);
    }

    public function reopen(User $user, Appraisal $appraisal): bool
    {
        return $this->submitAsAdviser($user, $appraisal);
    }
}
