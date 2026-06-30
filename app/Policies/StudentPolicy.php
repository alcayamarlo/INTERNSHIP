<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Student;
use App\Models\User;

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
}
