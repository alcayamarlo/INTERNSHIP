<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\SkillCategory;
use App\Models\User;

class SkillCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isRole(UserRole::Admin);
    }

    public function view(User $user, SkillCategory $category): bool
    {
        return $user->isRole(UserRole::Admin);
    }

    public function create(User $user): bool
    {
        return $user->isRole(UserRole::Admin);
    }

    public function update(User $user, SkillCategory $category): bool
    {
        return $user->isRole(UserRole::Admin);
    }

    public function delete(User $user, SkillCategory $category): bool
    {
        return $user->isRole(UserRole::Admin);
    }
}
