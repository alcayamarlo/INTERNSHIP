<?php

namespace App\Support;

use App\Models\User;

/**
 * The password policy for the first-login password change. The rules live here once:
 * App\Rules\StrongPassword enforces them on the server, and the Set New Password page receives
 * forClient() so its live checklist checks exactly the same things.
 */
final class PasswordPolicy
{
    public const MIN_LENGTH = 8;

    public const MAX_LENGTH = 64;

    /** Rule key => the message shown when a password breaks it (also the checklist order). */
    public const MESSAGES = [
        'length' => 'Use 8 to 64 characters.',
        'uppercase' => 'Add at least one uppercase letter (A-Z).',
        'lowercase' => 'Add at least one lowercase letter (a-z).',
        'number' => 'Add at least one number (0-9).',
        'special' => 'Add at least one special character, like ! @ # $ or %.',
    ];

    /**
     * @return list<string>  keys from MESSAGES for each rule the password breaks; empty means it passes
     */
    public static function failures(string $password): array
    {
        $length = mb_strlen($password);

        $passes = [
            'length' => $length >= self::MIN_LENGTH && $length <= self::MAX_LENGTH,
            'uppercase' => preg_match('/\p{Lu}/u', $password) === 1,
            'lowercase' => preg_match('/\p{Ll}/u', $password) === 1,
            'number' => preg_match('/\p{N}/u', $password) === 1,
            'special' => preg_match('/[^\p{L}\p{N}\s]/u', $password) === 1,
        ];

        return array_keys(array_filter($passes, fn (bool $ok) => ! $ok));
    }

    /** Everything the page's live checklist needs, so it mirrors the server rules. */
    public static function forClient(User $user): array
    {
        return [
            'min' => self::MIN_LENGTH,
            'max' => self::MAX_LENGTH,
            'messages' => self::MESSAGES,
        ];
    }
}
