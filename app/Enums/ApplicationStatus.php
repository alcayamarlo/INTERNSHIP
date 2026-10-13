<?php

namespace App\Enums;

enum ApplicationStatus: string
{
    case Applied = 'applied';
    case Submitted = 'submitted';
    case Reviewed = 'reviewed';
    case Interview = 'interview';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Completed = 'completed';
    case Withdrawn = 'withdrawn';

    public function label(): string
    {
        return match ($this) {
            self::Applied => 'Applied',
            self::Submitted => 'Submitted',
            self::Reviewed => 'Reviewed',
            self::Interview => 'Interview',
            self::Accepted => 'Accepted',
            self::Rejected => 'Rejected',
            self::Completed => 'Completed',
            self::Withdrawn => 'Withdrawn',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Applied => 'bg-info',
            self::Submitted => 'bg-secondary',
            self::Reviewed => 'bg-info',
            self::Interview => 'bg-primary',
            self::Accepted => 'bg-success',
            self::Rejected => 'bg-danger',
            self::Completed => 'bg-dark',
            self::Withdrawn => 'bg-secondary',
        };
    }

    /**
     * @return list<self>
     */
    public function allowedNextStatuses(): array
    {
        return match ($this) {
            self::Applied => [self::Reviewed, self::Interview, self::Rejected, self::Withdrawn],
            self::Submitted => [self::Reviewed, self::Rejected, self::Withdrawn],
            self::Reviewed => [self::Interview, self::Accepted, self::Rejected, self::Withdrawn],
            self::Interview => [self::Accepted, self::Rejected, self::Withdrawn],
            self::Accepted => [self::Completed],
            self::Rejected, self::Completed, self::Withdrawn => [],
        };
    }

    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->allowedNextStatuses(), true);
    }

    public function isActive(): bool
    {
        return in_array($this, [self::Applied, self::Submitted, self::Reviewed, self::Interview], true);
    }
}
