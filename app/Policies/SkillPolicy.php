<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Skill;
use App\Models\User;

class SkillPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isRole(UserRole::Admin);
    }

    public function view(User $user, Skill $skill): bool
    {
        return $user->isRole(UserRole::Admin);
    }

    public function create(User $user): bool
    {
        return $user->isRole(UserRole::Admin);
    }

    public function update(User $user, Skill $skill): bool
    {
        return $user->isRole(UserRole::Admin);
    }

    public function delete(User $user, Skill $skill): bool
    {
        return $user->isRole(UserRole::Admin);
    }
}
