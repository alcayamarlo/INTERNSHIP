<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class MessagePolicy
{
    public function view(User $user, User $recipient): Response
    {
        return $this->send($user, $recipient);
    }

    public function send(User $user, User $recipient): Response
    {
        if ($user->is($recipient)) {
            return Response::deny('You cannot message yourself.');
        }

        if ($user->isRole(UserRole::Student) && $recipient->isRole(UserRole::Student)) {
            return Response::deny('Students cannot message other students.');
        }

        return Response::allow();
    }
}
