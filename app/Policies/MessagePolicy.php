<?php

namespace App\Policies;

use App\Models\User;
use App\Services\MessagingEligibility;
use Illuminate\Auth\Access\Response;

/** Thin wrapper: the rules live in MessagingEligibility. */
class MessagePolicy
{
    public function __construct(private MessagingEligibility $eligibility) {}

    public function view(User $user, User $recipient): Response
    {
        return $this->eligibility->canView($user, $recipient);
    }

    public function send(User $user, User $recipient): Response
    {
        return $this->eligibility->canSend($user, $recipient);
    }
}
