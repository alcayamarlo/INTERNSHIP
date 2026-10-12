<?php

namespace App\Support;

use App\Models\AcademicSetting;

final class StudentIdFormat
{
    public const DEFAULT_PATTERN = '^SCC-\d{2}-\d{8}$';

    public static function pattern(): string
    {
        $pattern = trim((string) AcademicSetting::current()->student_id_pattern);

        return self::isValidPattern($pattern) ? $pattern : self::DEFAULT_PATTERN;
    }

    public static function isValidPattern(string $pattern): bool
    {
        return @preg_match(self::compile($pattern), '') !== false;
    }

    public static function matches(?string $studentId, ?string $pattern = null): bool
    {
        if (blank($studentId)) {
            return false;
        }

        $normalized = mb_strtoupper(trim($studentId));
        $pattern = $pattern === null
            ? self::pattern()
            : (self::isValidPattern(trim($pattern)) ? trim($pattern) : self::DEFAULT_PATTERN);

        return @preg_match(self::compile($pattern), $normalized) === 1;
    }

    private static function compile(string $pattern): string
    {
        $escapedPattern = '';
        $backslashes = 0;

        foreach (str_split($pattern) as $character) {
            if ($character === '~' && $backslashes % 2 === 0) {
                $escapedPattern .= '\\';
            }

            $escapedPattern .= $character;
            $backslashes = $character === '\\' ? $backslashes + 1 : 0;
        }

        return '~'.$escapedPattern.'~uD';
    }
}
