<?php

namespace App\Enums;

/**
 * The fixed skill type (replaces the admin-editable Skill Categories). Used by the skills
 * catalog and by student certificates/skills; it is not editable in the app.
 */
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

    /**
     * Maps an old Skill Category name to a type, or null when it cannot be told apart
     * (the caller then defaults to Technical and flags the skill for review).
     */
    public static function fromLegacyCategory(?string $category): ?self
    {
        $name = strtolower(trim(preg_replace('/\s+/', ' ', (string) $category)));
        if ($name === '') {
            return null;
        }

        $soft = ['soft', 'interpersonal', 'people', 'communication', 'behavioral', 'behavioural', 'personal', 'social', 'leadership', 'teamwork'];
        $technical = ['technical', 'tech', 'hard', 'programming', 'software', 'computer', 'it', 'engineering', 'database', 'web', 'networking', 'development', 'tool', 'tools'];
        $words = preg_split('/[^a-z]+/', $name, -1, PREG_SPLIT_NO_EMPTY);

        return match (true) {
            array_intersect($words, $soft) !== [] => self::Soft,
            array_intersect($words, $technical) !== [] => self::Technical,
            default => null,
        };
    }
}
