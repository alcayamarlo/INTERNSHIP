<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\StudentCompetency;
use App\Models\User;

class StudentCompetencyPolicy
{
    public function view(User $user, StudentCompetency $competency): bool
    {
        return $user->isRole(UserRole::Student)
            && $user->student?->id === $competency->student_id;
    }

    public function update(User $user, StudentCompetency $competency): bool
    {
        return $this->view($user, $competency);
    }

    public function delete(User $user, StudentCompetency $competency): bool
    {
        return $this->view($user, $competency);
    }
}
