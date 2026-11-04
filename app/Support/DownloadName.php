<?php

namespace App\Support;

use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\HeaderUtils;

/** Turns a user-typed title into a filename that is safe in a Content-Disposition header. */
class DownloadName
{
    public static function make(?string $title, string $storedPath): string
    {
        // Slashes, quotes, and control characters are not allowed in a download filename.
        $name = trim(preg_replace('/[\\\\\/"\x00-\x1F\x7F]+/u', '-', CatalogName::display($title)) ?? '', " -.");
        $extension = strtolower(pathinfo($storedPath, PATHINFO_EXTENSION));

        return ($name !== '' ? Str::limit($name, 150, '') : 'file').($extension !== '' ? '.'.$extension : '');
    }

    public static function inline(?string $title, string $storedPath): string
    {
        $name = self::make($title, $storedPath);
        $fallback = str_replace('%', '', Str::ascii($name)) ?: 'file';

        return HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_INLINE, $name, $fallback);
    }
}
