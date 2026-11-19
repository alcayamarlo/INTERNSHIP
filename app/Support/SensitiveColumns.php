<?php

namespace App\Support;

/**
 * The personal-data columns that are stored encrypted (Laravel's encrypted cast, key: APP_KEY), and a way to tell
 * an encrypted value from a plain one. The models declare the casts; this list is what `php artisan data:reencrypt`
 * walks, and what the tests check against the raw tables.
 *
 * An encrypted column cannot be searched, sorted or filtered in SQL. Read it through the model.
 */
final class SensitiveColumns
{
    /** @var array<string, list<string>> table => columns */
    public const COLUMNS = [
        'students' => [
            'date_of_birth', 'place_of_birth',
            'address', 'city', 'province', 'zip_code', 'provincial_address',
            'emergency_contact_name', 'emergency_contact_relationship', 'emergency_contact_address', 'emergency_contact_phone',
            'religion', 'civil_status',
        ],
        'users' => ['phone'],
        'character_references' => ['contact_number'],
    ];

    /** True when the value has the shape of an encrypted payload (base64 of JSON with iv, value and mac or tag). */
    public static function looksEncrypted(mixed $value): bool
    {
        if (! is_string($value) || $value === '' || strlen($value) < 40) {
            return false;
        }
        $json = base64_decode($value, true);
        if ($json === false) {
            return false;
        }
        $payload = json_decode($json, true);

        return is_array($payload) && isset($payload['iv'], $payload['value']) && (isset($payload['mac']) || isset($payload['tag']));
    }
}
