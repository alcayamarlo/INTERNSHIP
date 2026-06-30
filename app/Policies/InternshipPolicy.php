<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Internship;
use App\Models\User;

class InternshipPolicy
{
    public function update(User $user, Internship $internship): bool
    {
        return $user->isRole(UserRole::Employer)
            && $user->employer?->id === $internship->employer_id;
    }

    public function delete(User $user, Internship $internship): bool
    {
        return $this->update($user, $internship);
    }

    public function close(User $user, Internship $internship): bool
    {
        return $this->update($user, $internship);
    }
}
