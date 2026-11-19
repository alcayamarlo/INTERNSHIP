<?php

namespace App\Casts;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use DateTimeInterface;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

/**
 * A date stored encrypted (as the text "Y-m-d") and read as a Carbon date at midnight, like the `date` cast.
 * The stored value is Laravel's own encrypted payload, the same as the `encrypted` cast makes.
 *
 * @implements CastsAttributes<CarbonInterface|null, mixed>
 */
class EncryptedDate implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?CarbonInterface
    {
        if ($value === null || $value === '') {
            return null;
        }
        $plain = trim(Crypt::decryptString($value));

        return $plain === '' ? null : Carbon::createFromFormat('!Y-m-d', substr($plain, 0, 10));
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $date = $value instanceof DateTimeInterface ? Carbon::instance($value) : Carbon::parse((string) $value);

        return Crypt::encryptString($date->format('Y-m-d'));
    }
}
