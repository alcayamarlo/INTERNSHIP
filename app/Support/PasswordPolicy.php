<?php

namespace App\Support;

use App\Models\User;
use DateTimeInterface;

/**
 * The strong-password policy for the first-login password change. The rules live here once:
 * App\Rules\StrongPassword enforces them on the server, and the Set New Password page receives
 * forClient() so its live checklist checks exactly the same things.
 */
final class PasswordPolicy
{
    public const MIN_LENGTH = 12;

    public const MAX_LENGTH = 64;

    /** Rule key => the message shown when a password breaks it (also the checklist order). */
    public const MESSAGES = [
        'length' => 'Use 12 to 64 characters.',
        'uppercase' => 'Add at least one uppercase letter (A-Z).',
        'lowercase' => 'Add at least one lowercase letter (a-z).',
        'number' => 'Add at least one number (0-9).',
        'special' => 'Add at least one special character, like ! @ # $ or %.',
        'spaces' => 'Passwords cannot contain spaces.',
        'birthday' => 'Password cannot contain your birthday.',
        'name' => 'Password cannot contain your first or last name.',
        'identity' => 'Password cannot contain your username or ID number.',
        'common' => 'This password is too common. Choose something harder to guess.',
        'pattern' => 'Avoid simple patterns like 123456, abcdef, qwerty, or repeated characters like aaaa.',
    ];

    /**
     * Words that make a password easy to guess even with numbers and symbols added
     * ("Password123!", "Qwerty12345!"). Matched anywhere in the password, after undoing
     * look-alike swaps such as @ for a and 0 for o.
     */
    public const COMMON_WORDS = [
        'password', 'passwd', 'qwerty', 'qwertz', 'azerty', 'asdfgh', 'zxcvbn', 'letmein', 'welcome',
        'iloveyou', 'iloveu', 'mahalkita', 'sunshine', 'princess', 'football', 'baseball', 'basketball',
        'superman', 'batman', 'starwars', 'whatever', 'freedom', 'changeme', 'default', 'monkey',
        'dragon', 'master', 'shadow', 'trustno', 'secret', 'abc123', 'admin123', 'skillbridge',
        'cecilia', 'philippines', 'pilipinas',
    ];

    /** Straight runs that count as a "simple pattern"; keyboard rows need 5 in a row so "Liberty" passes. */
    private const SEQUENCES = ['0123456789' => 4, 'abcdefghijklmnopqrstuvwxyz' => 4, 'qwertyuiop' => 5, 'asdfghjkl' => 5, 'zxcvbnm' => 5];

    /** Look-alike characters people swap in, undone before the common-word check. */
    private const LOOKALIKES = ['@' => 'a', '4' => 'a', '3' => 'e', '1' => 'i', '!' => 'i', '0' => 'o', '$' => 's', '5' => 's', '7' => 't'];

    /**
     * Checks a password against every rule.
     *
     * @param  array{birthday?: list<string>, name?: list<string>, identity?: list<string>}  $personal
     *         lowercase pieces of the user's own details the password must not contain (see personalInfo())
     * @return list<string>  keys from MESSAGES for each rule the password breaks; empty means it passes
     */
    public static function failures(string $password, array $personal = []): array
    {
        $lower = mb_strtolower($password);
        // Separators are ignored for personal details, so "05/15/2008" still counts as a birthday.
        $compact = preg_replace('/[^\p{L}\p{N}]+/u', '', $lower) ?? $lower;
        $length = mb_strlen($password);

        $passes = [
            'length' => $length >= self::MIN_LENGTH && $length <= self::MAX_LENGTH,
            'uppercase' => preg_match('/\p{Lu}/u', $password) === 1,
            'lowercase' => preg_match('/\p{Ll}/u', $password) === 1,
            'number' => preg_match('/\p{N}/u', $password) === 1,
            'special' => preg_match('/[^\p{L}\p{N}\s]/u', $password) === 1,
            'spaces' => preg_match('/\s/u', $password) === 0,
            'birthday' => ! self::containsAny($compact, $personal['birthday'] ?? []),
            'name' => ! self::containsAny($compact, $personal['name'] ?? []),
            'identity' => ! self::containsAny($compact, $personal['identity'] ?? []),
            'common' => ! self::containsAny($lower, self::COMMON_WORDS) && ! self::containsAny(strtr($lower, self::LOOKALIKES), self::COMMON_WORDS),
            'pattern' => ! self::hasSimplePattern($lower),
        ];

        return array_keys(array_filter($passes, fn (bool $ok) => ! $ok));
    }

    /**
     * The user's own details a password must not contain: birthday in every common format,
     * first, middle, and last name, the email username, and the school ID number.
     *
     * @return array{birthday: list<string>, name: list<string>, identity: list<string>}
     */
    public static function personalInfo(User $user): array
    {
        $student = $user->student;

        $names = array_merge(
            preg_split('/\s+/u', (string) $user->name) ?: [],
            preg_split('/\s+/u', (string) $student?->first_name) ?: [],
            preg_split('/\s+/u', (string) $student?->middle_name) ?: [],
            preg_split('/\s+/u', (string) $student?->last_name) ?: [],
        );

        $identity = [strstr((string) $user->email, '@', true) ?: ''];
        if ($idNumber = (string) $student?->student_id_number) {
            // "SCC-22-00012345" also blocks "scc2200012345", "2200012345", and "00012345".
            $identity[] = $idNumber;
            $identity[] = preg_replace('/\D/', '', $idNumber);
            preg_match_all('/\d{4,}/', $idNumber, $chunks);
            array_push($identity, ...$chunks[0]);
        }

        return [
            'birthday' => $student?->date_of_birth ? self::birthdayForms($student->date_of_birth) : [],
            'name' => self::tokens($names, 3),
            'identity' => self::tokens($identity, 4),
        ];
    }

    /** Everything the page's live checklist needs, so it mirrors the server rules. */
    public static function forClient(User $user): array
    {
        return [
            'min' => self::MIN_LENGTH,
            'max' => self::MAX_LENGTH,
            'messages' => self::MESSAGES,
            'common' => self::COMMON_WORDS,
            'sequences' => self::SEQUENCES,
            'lookalikes' => self::LOOKALIKES,
            'personal' => self::personalInfo($user),
        ];
    }

    /** MMDDYYYY, DDMMYYYY, YYYYMMDD, 2-digit-year and unpadded forms, MMDD, DDMM, and the year alone. */
    private static function birthdayForms(DateTimeInterface $date): array
    {
        $formats = ['mdY', 'dmY', 'Ymd', 'mdy', 'dmy', 'ymd', 'njY', 'jnY', 'md', 'dm', 'Y'];

        return array_values(array_unique(array_map(fn (string $format) => $date->format($format), $formats)));
    }

    /** Lowercase letters and digits only, at least $min long, no duplicates. */
    private static function tokens(array $values, int $min): array
    {
        $tokens = array_map(fn ($value) => preg_replace('/[^\p{L}\p{N}]+/u', '', mb_strtolower((string) $value)) ?? '', $values);

        return array_values(array_unique(array_filter($tokens, fn (string $token) => mb_strlen($token) >= $min)));
    }

    private static function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if ($needle !== '' && str_contains($haystack, (string) $needle)) {
                return true;
            }
        }

        return false;
    }

    /** Four of the same character in a row ("aaaa", "1111") or a straight run ("1234", "dcba", "qwert"). */
    private static function hasSimplePattern(string $lower): bool
    {
        if (preg_match('/(.)\1{3,}/u', $lower) === 1) {
            return true;
        }

        foreach (self::SEQUENCES as $run => $length) {
            foreach ([$run, strrev($run)] as $sequence) {
                for ($i = 0; $i + $length <= strlen($sequence); $i++) {
                    if (str_contains($lower, substr($sequence, $i, $length))) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
