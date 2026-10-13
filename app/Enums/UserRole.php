<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Student = 'student';
    case Employer = 'employer';
    case Coordinator = 'coordinator';

    /**
     * @return list<self>
     */
    public static function all(): array
    {
        return self::cases();
    }

    /**
     * Roles that admins may assign without creating another administrator.
     *
     * @return list<self>
     */
    public static function assignable(): array
    {
        return [self::Student, self::Coordinator, self::Employer];
    }

    public function pluralLabel(): string
    {
        return match ($this) {
            self::Admin => 'Administrators',
            self::Student => 'Students',
            self::Employer => 'Employers',
            self::Coordinator => 'Coordinators',
        };
    }

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
