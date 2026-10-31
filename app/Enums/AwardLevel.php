<?php

namespace App\Enums;

/** Case order is the display order on the profile, resume, and employer view. */
enum AwardLevel: string
{
    case College = 'college';
    case SeniorHigh = 'senior_high';
    case JuniorHigh = 'junior_high';
    case Elementary = 'elementary';
    // Holds the old one-paragraph "Honors / awards" text until the student sorts it out.
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::College => 'College',
            self::SeniorHigh => 'Senior High School',
            self::JuniorHigh => 'Junior High School',
            self::Elementary => 'Elementary',
            self::Other => 'Other',
        };
    }

    /** Levels a student can pick when adding an award. */
    public static function selectable(): array
    {
        return array_values(array_filter(self::cases(), fn (self $level) => $level !== self::Other));
    }
}
