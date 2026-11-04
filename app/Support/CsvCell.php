<?php

namespace App\Support;

/**
 * Stops spreadsheet apps from running user-typed text as a formula (CSV injection): a cell that starts
 * with =, +, -, @, tab, or carriage return gets a leading apostrophe. Plain numbers are left alone.
 */
class CsvCell
{
    public static function safe(mixed $value): mixed
    {
        if (! is_string($value) || $value === '' || is_numeric($value)) {
            return $value;
        }

        return str_contains("=+-@\t\r", $value[0]) ? "'".$value : $value;
    }

    public static function row(array $values): array
    {
        return array_map(self::safe(...), $values);
    }
}
