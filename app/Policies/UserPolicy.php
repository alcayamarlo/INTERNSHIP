<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

class UserPolicy
{
    public function update(User $user, User $model): bool
    {
        return $user->isRole(UserRole::Admin);
    }

    public function delete(User $user, User $model): bool
    {
        return $user->isRole(UserRole::Admin) && $user->id !== $model->id;
    }
}
