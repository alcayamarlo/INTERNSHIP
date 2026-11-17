<?php

namespace App\Support;

/**
 * IP addresses are personal data, so the system stores only a keyed hash of them.
 *
 * HMAC-SHA256 with a secret (IP_HASH_KEY), not a plain hash: there are only about 4 billion IPv4
 * addresses, so an unkeyed hash can be reversed by trying them all. The same IP always gives the
 * same hash, so "failed sign-ins per IP" and searching by IP still work.
 */
class IpHash
{
    /** Length of a stored hash (hex of SHA-256). An IPv4/IPv6 address is never this long. */
    public const LENGTH = 64;

    /** Hash to store; null when there is no IP, or no key is set (then no IP is recorded at all). */
    public static function make(?string $ip): ?string
    {
        $key = (string) config('privacy.ip_hash_key');
        if ($ip === null || $ip === '' || $key === '') {
            return null;
        }

        return hash_hmac('sha256', $ip, $key);
    }

    /** Key for rate limiters: the hash, or the plain address when no key is set (never one shared key). */
    public static function limiterKey(?string $ip): string
    {
        return self::make($ip) ?? (string) $ip;
    }

    public static function isHash(?string $value): bool
    {
        return $value !== null && strlen($value) === self::LENGTH && ctype_xdigit($value);
    }

    /** Short code shown to admins, such as "ip·3f9a1c2b"; the full hash stays in the database. */
    public static function label(?string $stored): string
    {
        return match (true) {
            blank($stored) => '—',
            self::isHash($stored) => 'ip·'.substr($stored, 0, 8),
            default => 'ip·'.substr(hash('sha256', $stored), 0, 8), // an address not migrated yet is never shown
        };
    }

    /** What an admin's search term means: a real IP address matches its hash exactly; otherwise a (partial) code. */
    public static function searchValue(string $term): array
    {
        $term = trim($term);
        $code = ltrim(str_replace('ip·', '', $term));

        return [
            'hash' => filter_var($term, FILTER_VALIDATE_IP) ? self::make($term) : null,
            'prefix' => ctype_xdigit($code) && strlen($code) >= 4 ? strtolower($code) : null,
        ];
    }
}
