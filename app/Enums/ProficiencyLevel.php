<?php

namespace App\Enums;

enum ProficiencyLevel: string
{
    case Beginner = 'beginner';
    case Intermediate = 'intermediate';
    case Advanced = 'advanced';
    case Expert = 'expert';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function score(): int
    {
        return match ($this) {
            self::Beginner => 25,
            self::Intermediate => 50,
            self::Advanced => 75,
            self::Expert => 100,
        };
    }
}
