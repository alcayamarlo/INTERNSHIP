<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

class MessagePolicy
{
    public function view(User $user, User $recipient): bool
    {
        return $this->send($user, $recipient);
    }

    public function send(User $user, User $recipient): bool
    {
        return match ($user->role) {
            UserRole::Student => $recipient->isRole(UserRole::Employer, UserRole::Coordinator),
            UserRole::Employer, UserRole::Coordinator => $recipient->isRole(UserRole::Student),
            default => false,
        };
    }
}
