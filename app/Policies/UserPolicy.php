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
        return $this->archive($user, $model);
    }

    public function archive(User $user, User $model): bool
    {
        return $user->isRole(UserRole::Admin)
            && $user->id !== $model->id
            && $model->archived_at === null;
    }

    public function restore(User $user, User $model): bool
    {
        return $user->isRole(UserRole::Admin)
            && $user->id !== $model->id
            && $model->archived_at !== null;
    }

    /** The Delete button on the Active list: any account but your own (the controller adds the record checks). */
    public function deleteNow(User $user, User $model): bool
    {
        return $user->isRole(UserRole::Admin) && $user->id !== $model->id;
    }

    public function forceDelete(User $user, User $model): bool
    {
        return $user->isRole(UserRole::Admin)
            && $user->id !== $model->id
            && $model->archived_at !== null;
    }
}
