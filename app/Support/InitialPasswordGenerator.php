<?php

namespace App\Support;

use DateTimeInterface;

final class InitialPasswordGenerator
{
    public static function fromBirthdate(DateTimeInterface $dateOfBirth): string
    {
        return $dateOfBirth->format('mdY');
    }
}
