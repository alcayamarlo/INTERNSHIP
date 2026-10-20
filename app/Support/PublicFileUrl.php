<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use App\Support\UploadDisks;

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

        $disk = UploadDisks::public();
        if (! Storage::disk($disk)->exists($path)) {
            return null;
        }

        // Local disks stay relative so a wrong APP_URL (e.g. on Railway) cannot
        // break images; the /storage fallback route serves them without a symlink.
        if (config("filesystems.disks.{$disk}.driver") === 'local') {
            return '/storage/'.implode('/', array_map('rawurlencode', $segments));
        }

        return Storage::disk($disk)->url($path);
    }
}
