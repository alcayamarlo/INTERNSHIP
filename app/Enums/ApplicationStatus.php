<?php

namespace App\Enums;

enum ApplicationStatus: string
{
    case Submitted = 'submitted';
    case Reviewed = 'reviewed';
    case Interview = 'interview';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Submitted => 'Submitted',
            self::Reviewed => 'Reviewed',
            self::Interview => 'Interview',
            self::Accepted => 'Accepted',
            self::Rejected => 'Rejected',
            self::Completed => 'Completed',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Submitted => 'bg-secondary',
            self::Reviewed => 'bg-info',
            self::Interview => 'bg-primary',
            self::Accepted => 'bg-success',
            self::Rejected => 'bg-danger',
            self::Completed => 'bg-dark',
        };
    }
}
