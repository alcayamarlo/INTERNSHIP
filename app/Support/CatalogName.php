<?php

namespace App\Support;

use Illuminate\Support\Str;

class CatalogName
{
    public static function display(?string $name): string
    {
        return preg_replace('/\s+/u', ' ', trim((string) $name)) ?? trim((string) $name);
    }

    public static function normalized(?string $name): string
    {
        return Str::lower(self::display($name));
    }
}
