<?php

namespace App\Enums;

enum SkillType: string
{
    case Technical = 'technical';
    case Soft = 'soft';

    public function label(): string
    {
        return match ($this) {
            self::Technical => 'Technical',
            self::Soft => 'Soft Skill',
        };
    }
}
