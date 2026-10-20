<?php

namespace App\Support;

final class UploadDisks
{
    public static function public(): string
    {
        return (string) config('filesystems.uploads.public_disk', 'public');
    }

    public static function private(): string
    {
        return (string) config('filesystems.uploads.private_disk', 'private');
    }

    /**
     * @return list<string>
     */
    public static function search(): array
    {
        return array_values(array_unique([
            self::private(),
            self::public(),
            'private',
            'public',
        ]));
    }
}
