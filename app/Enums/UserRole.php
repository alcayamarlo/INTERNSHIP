<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Student = 'student';
    case Employer = 'employer';
    case Coordinator = 'coordinator';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::Student => 'Student',
            self::Employer => 'Employer',
            self::Coordinator => 'Institution Coordinator',
        };
    }

    public function dashboardRoute(): string
    {
        return match ($this) {
            self::Admin => 'admin.dashboard',
            self::Student => 'student.dashboard',
            self::Employer => 'employer.dashboard',
            self::Coordinator => 'coordinator.dashboard',
        };
    }
}
