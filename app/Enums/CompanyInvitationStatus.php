<?php

namespace App\Enums;

enum CompanyInvitationStatus: string
{
    // The email was sent; the company has not registered yet.
    case Sent = 'sent';
    // The company registered from the link.
    case Accepted = 'accepted';
    // The link is too old and no longer works.
    case Expired = 'expired';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Sent => 'bg-warning text-dark',
            self::Accepted => 'bg-success',
            self::Expired => 'bg-dark',
        };
    }
}
