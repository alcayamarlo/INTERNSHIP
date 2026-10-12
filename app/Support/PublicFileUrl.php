<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

final class PublicFileUrl
{
    public static function relative(?string $path): ?string
    {
        if ($path === null || trim($path) === '') {
            return null;
        }

        $segments = explode('/', trim($path, '/'));

        if (in_array('', $segments, true) || in_array('.', $segments, true) || in_array('..', $segments, true)) {
            return null;
        }

        foreach ($segments as $segment) {
            if (str_contains($segment, '\\')) {
                return null;
            }
        }

        $path = implode('/', $segments);

        if (! Storage::disk('public')->exists($path)) {
            return null;
        }

        return '/storage/'.implode('/', array_map('rawurlencode', $segments));
    }
}
