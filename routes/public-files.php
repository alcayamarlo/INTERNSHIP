<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

/*
 * Serves public-disk uploads (profile pictures, logos) when the
 * public/storage symlink is missing, as on fresh Railway deploys where
 * `php artisan storage:link` has not run. The web server serves the file
 * directly whenever the symlink exists, so this route is only a fallback.
 */
Route::get('/storage/{path}', function (string $path) {
    abort_unless(preg_match('#\A(profiles|logos)/[A-Za-z0-9._/-]+\z#', $path) === 1, 404);
    abort_if(str_contains($path, '..'), 404);

    $disk = Storage::disk('public');
    abort_unless($disk->exists($path), 404);

    return $disk->response($path, null, [
        'Cache-Control' => 'public, max-age=86400',
        'X-Content-Type-Options' => 'nosniff',
    ]);
})->where('path', '.*')->name('public-files.show');
