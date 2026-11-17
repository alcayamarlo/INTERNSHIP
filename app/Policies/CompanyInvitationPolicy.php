<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\CompanyInvitation;
use App\Models\User;

class CompanyInvitationPolicy
{
    /** Coordinators see their own invitations; admins see all (read-only). */
    public function viewAny(User $user): bool
    {
        return $this->isCoordinator($user) || $user->isRole(UserRole::Admin);
    }

    /** Only coordinators invite companies. */
    public function create(User $user): bool
    {
        return $this->isCoordinator($user);
    }

    /** Only the coordinator who sent it can resend it. */
    public function resend(User $user, CompanyInvitation $invitation): bool
    {
        return $this->isCoordinator($user) && (int) $invitation->invited_by_user_id === (int) $user->id;
    }

    /** Only an administrator moves the expiry date, and not of an invitation that was already accepted. */
    public function changeExpiry(User $user, CompanyInvitation $invitation): bool
    {
        return $user->isRole(UserRole::Admin) && $invitation->status !== \App\Enums\CompanyInvitationStatus::Accepted;
    }

    private function isCoordinator(User $user): bool
    {
        return $user->isRole(UserRole::Coordinator) && $user->coordinator !== null;
    }
}
