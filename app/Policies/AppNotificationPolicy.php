<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\AppNotification;
use App\Models\User;

class AppNotificationPolicy
{
    public function view(User $user, AppNotification $notification): bool
    {
        return $notification->user_id === $user->id;
    }

    public function update(User $user, AppNotification $notification): bool
    {
        return $this->view($user, $notification);
    }
}
