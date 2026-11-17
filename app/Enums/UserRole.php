<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Student = 'student';
    case Employer = 'employer';
    case Coordinator = 'coordinator';

    /** The company's on-site supervisor: created by the HR account, handles only the interns assigned to them. */
    case Supervisor = 'supervisor';

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
            self::Employer => 'HR / Company accounts',
            self::Supervisor => 'Supervisors',
            self::Coordinator => 'Coordinators',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::Student => 'Student',
            self::Employer => 'HR / Company',
            self::Coordinator => 'Coordinator',
            self::Supervisor => 'Supervisor',
        };
    }

    /** Route-name prefix of the OJT supervision pages (My Interns): the supervisor has their own area. */
    public function ojtRoutePrefix(): string
    {
        return $this === self::Supervisor ? 'supervisor' : 'employer';
    }

    public function dashboardRoute(): string
    {
        return match ($this) {
            self::Admin => 'admin.dashboard',
            self::Student => 'student.dashboard',
            self::Employer => 'employer.dashboard',
            self::Coordinator => 'coordinator.dashboard',
            self::Supervisor => 'supervisor.dashboard',
        };
    }
}
