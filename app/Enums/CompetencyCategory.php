<?php

namespace App\Enums;

enum CompetencyCategory: string
{
    case Skill = 'skill';
    case Technical = 'technical';
    case Soft = 'soft';
    case Certification = 'certification';
    case Training = 'training';
    case Seminar = 'seminar';
    case Workshop = 'workshop';

    public function label(): string
    {
        return match ($this) {
            self::Skill => 'Skill',
            self::Technical => 'Technical Skill',
            self::Soft => 'Soft Skill',
            self::Certification => 'Certification',
            self::Training => 'Training',
            self::Seminar => 'Seminar',
            self::Workshop => 'Workshop',
        };
    }
}
