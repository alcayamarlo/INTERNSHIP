<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Announcement;
use App\Models\User;

class AnnouncementPolicy
{
    public function create(User $user): bool
    {
        return $user->isRole(UserRole::Admin);
    }

    public function delete(User $user, Announcement $announcement): bool
    {
        return $user->isRole(UserRole::Admin);
    }
}
